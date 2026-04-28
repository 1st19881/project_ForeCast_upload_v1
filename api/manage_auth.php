<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// ตรวจสอบสิทธิ์ Admin Level 99
if (!isset($_SESSION['user_code']) || (int)$_SESSION['aut_level'] < 99) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/database.php';
require_once '../config/intra_db.php';

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'list') {
        $conn = getDbConnection();
        if (!$conn) throw new Exception('เชื่อมต่อฐานข้อมูลสิทธิ์ไม่ได้ (HRMS)');

        $sql = "SELECT AUT_ID, CODEMPID, AUT_NAME, AUT_LEVEL, AUT_ACTIVE, AUT_PRIVILEGE, AUT_TYPE 
                FROM HRMS_AUTH 
                WHERE (UPPER(AUT_PRIVILEGE) = 'FORECAST' OR UPPER(AUT_PRIVILEGE) = 'SYSTEM')
                ORDER BY CREATE_DATE DESC";
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        $data = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC | OCI_RETURN_NULLS)) {
            // Data is already UTF-8 due to AL32UTF8 connection setting
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        oci_close($conn);

    } elseif ($action === 'lookup') {
        $conn = getIntraConnection();
        if (!$conn) throw new Exception('เชื่อมต่อฐานข้อมูลผู้ใช้งานไม่ได้ (SAGDB)');

        $codempid = strtoupper($_GET['codempid'] ?? '');
        $sql = "SELECT USERS_FNAMETH, USERS_LNAMETH FROM intra.users WHERE UPPER(USERS_EMPCODE) = :codempid AND USERS_STATUS = '1'";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":codempid", $codempid);
        oci_execute($stid);

        if ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $fNameTh = iconv('TIS-620', 'UTF-8//IGNORE', $row['USERS_FNAMETH']);
            $lNameTh = iconv('TIS-620', 'UTF-8//IGNORE', $row['USERS_LNAMETH']);
            echo json_encode(['status' => 'success', 'fullname' => trim($fNameTh . ' ' . $lNameTh)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        }
        oci_close($conn);

    } elseif ($action === 'save') {
        $conn = getDbConnection();
        if (!$conn) throw new Exception('เชื่อมต่อฐานข้อมูลสิทธิ์ไม่ได้ (HRMS)');

        $auth_id = $_POST['auth_id'] ?? '';
        $codempid = strtoupper($_POST['codempid'] ?? '');
        $aut_name = $_POST['aut_name'] ?? '';
        $aut_level = $_POST['aut_level'] ?? '9';
        $aut_type = $_POST['aut_type'] ?? 'User';
        $aut_active = $_POST['aut_active'] ?? 'Y';
        $aut_privilege = 'forecast';

        if (empty($auth_id)) {
            $sql = "INSERT INTO HRMS_AUTH 
                    (AUT_ID, CODEMPID, AUT_NAME, AUT_LEVEL, AUT_ACTIVE, AUT_PRIVILEGE, AUT_TYPE, CREATE_DATE) 
                    VALUES (REGEXP_REPLACE(RAWTOHEX(SYS_GUID()), '([0-9A-F]{32})', '\\1'), :codempid, :aut_name, :aut_level, :aut_active, :aut_privilege, :aut_type, SYSDATE)";
        } else {
            $sql = "UPDATE HRMS_AUTH 
                    SET AUT_LEVEL = :aut_level, 
                        AUT_ACTIVE = :aut_active, 
                        AUT_TYPE = :aut_type,
                        AUT_NAME = :aut_name 
                    WHERE AUT_ID = :auth_id";
        }

        $stid = oci_parse($conn, $sql);
        if (empty($auth_id)) {
            oci_bind_by_name($stid, ":aut_privilege", $aut_privilege);
            oci_bind_by_name($stid, ":codempid", $codempid);
        } else {
            oci_bind_by_name($stid, ":auth_id", $auth_id);
        }
        oci_bind_by_name($stid, ":aut_name", $aut_name);
        oci_bind_by_name($stid, ":aut_level", $aut_level);
        oci_bind_by_name($stid, ":aut_type", $aut_type);
        oci_bind_by_name($stid, ":aut_active", $aut_active);

        if (oci_execute($stid)) {
            echo json_encode(['status' => 'success']);
        } else {
            $e = oci_error($stid);
            throw new Exception('Database Error: ' . $e['message']);
        }
        oci_close($conn);

    } elseif ($action === 'delete') {
        $conn = getDbConnection();
        if (!$conn) throw new Exception('เชื่อมต่อฐานข้อมูลสิทธิ์ไม่ได้ (HRMS)');

        $auth_id = $_POST['auth_id'] ?? '';
        if (empty($auth_id)) throw new Exception('Invalid Request');

        $sql = "DELETE FROM HRMS_AUTH WHERE AUT_ID = :auth_id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":auth_id", $auth_id);

        if (oci_execute($stid)) {
            echo json_encode(['status' => 'success']);
        } else {
            $e = oci_error($stid);
            throw new Exception('Delete failed: ' . $e['message']);
        }
        oci_close($conn);
    } else {
        throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
