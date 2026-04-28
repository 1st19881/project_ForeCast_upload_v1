<?php
header('Content-Type: application/json');
require_once '../config/conn.php';

$q = isset($_GET['q']) ? strtoupper($_GET['q']) : '';
$plant = isset($_GET['plant']) ? $_GET['plant'] : '';

if (empty($plant)) {
    echo json_encode([]);
    exit;
}

/**
 * ฟังก์ชันจัดรูปแบบ Part Number ให้เป็น 18 หลักถ้าเป็นตัวเลขล้วน
 */
function formatPartNumber($partNo) {
    if (empty($partNo)) return '';
    $trimmed = trim($partNo);
    return ctype_digit($trimmed) ? str_pad($trimmed, 18, '0', STR_PAD_LEFT) : $trimmed;
}

// ถ้าสิ่งที่พิมพ์มาเป็นตัวเลขล้วน ให้แปลงเป็นแบบ 18 หลักก่อนค้นหา
$q_formatted = formatPartNumber($q);

$conn = @oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
if (!$conn) {
    echo json_encode(['error' => 'Connection failed']);
    exit;
}

try {
    // List unique parts for the selected plant using correct column names
    $sql = "SELECT * FROM (
                SELECT PART_NO, MAX(PART_NAME) as PART_NAME
                FROM WEB.FC_FORECAST 
                WHERE PLANT = :plant 
                AND FORECAST_STATUS = 'A'
                AND (UPPER(PART_NO) LIKE :q OR UPPER(PART_NAME) LIKE :q)
                GROUP BY PART_NO
                ORDER BY PART_NO ASC
            ) WHERE ROWNUM <= 20";
            
    $stmt = oci_parse($conn, $sql);
    $query_param = "%$q_formatted%";
    oci_bind_by_name($stmt, ':plant', $plant);
    oci_bind_by_name($stmt, ':q', $query_param);
    
    oci_execute($stmt);
    
    $results = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
        if (ctype_digit($row['PART_NO'])) {
            $row['PART_NO'] = ltrim($row['PART_NO'], '0') ?: '0';
        }
        $results[] = $row;
    }
    
    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
