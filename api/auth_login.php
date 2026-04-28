<?php
// api/auth_login.php
header('Content-Type: application/json; charset=utf-8');

// ปิดการพ่น HTML error ออกไปตรงๆ ให้เก็บไว้ใน Buffer แทน
ob_start();
session_start();

// ตั้งค่าให้แสดง error แต่จะดักเอามาตอบเป็น JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // ปิดไม่ให้พ่น HTML ออกไป

try {
    if (!function_exists('oci_connect')) {
        throw new Exception('PHP OCI8 extension is not enabled on this server');
    }

    require_once '../config/conn.php';
    require_once '../config/database.php';
    require_once '../config/intra_db.php';
    
    // 1. เชื่อมต่อฐานข้อมูลหลัก (Intra) - ใช้ SAGDB ผ่าน config
    $conn = getIntraConnection();

    if (!$conn) {
        $e = oci_error();
        throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลหลักได้: ' . ($e['message'] ?? 'Unknown'));
    }

    $username = strtoupper($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception('กรุณากรอก Username และ Password');
    }

    $sql = "SELECT * FROM intra.users WHERE upper(USERS_EMPCODE) = :u_name AND USERS_STATUS = '1'";
    $rs = oci_parse($conn, $sql);
    oci_bind_by_name($rs, ":u_name", $username);
    
    if (!@oci_execute($rs)) {
        $e = oci_error($rs);
        throw new Exception('Query Execution Error (Intra): ' . $e['message']);
    }

    if (($Row = oci_fetch_array($rs, OCI_ASSOC | OCI_RETURN_NULLS)) != false) {
        if ($Row['USERS_PASSWORD'] == $password) {
            $userCode = $Row['USERS_EMPCODE'];

            // 2. เชื่อมต่อฐานข้อมูล HRMS เพื่อตรวจสอบสิทธิ์
            $h_conn = getDbConnection();
            if (!$h_conn) {
                throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลสิทธิ์ได้ (HRMS)');
            }

            $authData = null;
            $authSql = "SELECT AUT_LEVEL, AUT_TYPE, AUT_NAME 
                        FROM HRMS_AUTH 
                        WHERE UPPER(TRIM(CODEMPID)) = UPPER(:codempid) 
                        AND AUT_ACTIVE = 'Y' 
                        AND (UPPER(AUT_PRIVILEGE) = 'FORECAST' OR UPPER(AUT_PRIVILEGE) = 'SYSTEM')
                        ORDER BY AUT_LEVEL DESC";
            $h_rs = oci_parse($h_conn, $authSql);
            $trimmedUserCode = trim($userCode);
            oci_bind_by_name($h_rs, ":codempid", $trimmedUserCode);
            
            if (oci_execute($h_rs)) {
                $authData = oci_fetch_array($h_rs, OCI_ASSOC | OCI_RETURN_NULLS);
            } else {
                $e = oci_error($h_rs);
                @oci_close($h_conn);
                throw new Exception('Privilege Check Error (HRMS): ' . $e['message']);
            }
            
            @oci_close($h_conn); // ปิดการเชื่อมต่อ HRMS ทันทีที่เช็คเสร็จ
            
            if (!$authData) {
                throw new Exception('คุณยังไม่มีสิทธิ์เข้าใช้งานระบบ Forecast กรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์ (FORECAST)');
            }
            
            // เก็บชื่อและระดับสิทธิ์ลง Session
            $_SESSION['aut_level'] = (int)$authData['AUT_LEVEL'];
            $_SESSION['aut_type']  = $authData['AUT_TYPE'];
            $_SESSION['aut_name']  = isset($authData['AUT_NAME']) ? iconv('TIS-620', 'UTF-8//IGNORE', $authData['AUT_NAME']) : '';

            $fNameThRaw = $Row['USERS_FNAMETH'] ?? '';
            $lNameThRaw = $Row['USERS_LNAMETH'] ?? '';
            
            // แปลงรหัสจาก TIS-620 เป็น UTF-8 เองแบบ Manual
            $fNameTh = iconv('TIS-620', 'UTF-8//IGNORE', $fNameThRaw);
            $lNameTh = iconv('TIS-620', 'UTF-8//IGNORE', $lNameThRaw);
            $fNameEn = $Row['USERS_FNAME'] ?? '';
            $lNameEn = $Row['USERS_LNAME'] ?? '';
            
            $fullnameTh = trim($fNameTh . ' ' . $lNameTh);
            $fullnameEn = trim($fNameEn . ' ' . $lNameEn);
            $perm['Fullname'] = !empty($fullnameTh) ? $fullnameTh : $fullnameEn;
            
            $perm['Status'] = $Row['USERS_GROUP'];
            $perm['Usersite'] = $Row['USERS_SITEID'];
            $perm['CodComp'] = $Row['USERS_CODECOMP'];
            $perm['Department'] = $Row['USERS_DEPARTMENT'];
            $perm['CostCenter'] = $Row['USERS_COSTCENTER'];
            $perm['Position'] = substr("0000" . ($Row['USERS_POSITION'] ?? ''), -4);

            $_SESSION['user_id'] = $Row['USERS_ID'];
            $_SESSION['user_code'] = $Row["USERS_EMPCODE"];
            $_SESSION['user_name'] = $fNameTh;
            $_SESSION['fullname'] = !empty($fullnameTh) ? $fullnameTh : $fullnameEn;
            $_SESSION['codcomp'] = $Row["USERS_CODECOMP"];
            
            // Logic for Plant No mapping
            $ArrPlantNo = array(
                "10" => "1100", "11" => "1101",
                "20" => "1200", "21" => "1201", "22" => "1202", "23" => "1203", 
                "30" => "1300", "40" => "1400"
            );
            $S_inx = substr($Row["USERS_COSTCENTER"], 0, 2);
            $S_Plant_No = isset($ArrPlantNo[$S_inx]) ? $ArrPlantNo[$S_inx] : "1100";
            
            $_SESSION['plant_no'] = $S_Plant_No;

            ob_clean();
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception('Password ไม่ถูกต้อง');
        }
    } else {
        throw new Exception('ไม่พบรายชื่อผู้ใช้งานหรือสถานะไม่ปกติ');
    }

    @oci_close($conn);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>
