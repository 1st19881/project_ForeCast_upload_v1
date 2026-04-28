<?php
// api/get_customers.php - Direct Connection Version
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '';

if (empty($plant)) {
    echo json_encode(['error' => 'No plant selected']);
    exit;
}

// ต่อตรงแบบไฟล์ Test
$user = "DSP";
$pwd  = "dsp";
$db   = "ECCERP";
$lang = "WE8DEC";

$conn = @oci_connect($user, $pwd, $db, $lang);
if (!$conn) {
    $e = oci_error();
    echo json_encode(['error' => 'SAP Connect Failed: ' . $e['message']]);
    exit;
}

$sql = "SELECT 
            t1.kunnr, 
            t2.name1
        FROM 
            sapsr3.VBAK t1
        JOIN 
            sapsr3.KNA1 t2 ON t1.mandt = t2.mandt AND t1.kunnr = t2.kunnr
        WHERE 
            t1.mandt = '400'
            AND t1.vkorg = :plant
        GROUP BY 
            t1.kunnr, 
            t2.name1
        ORDER BY
            t2.name1 ASC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':plant', $plant);

if (!@oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo json_encode(['error' => 'SQL Execution Failed: ' . $e['message']]);
    exit;
}

$results = [];
// ใช้รหัส ID แทน Constant เผื่อ PHP หาไม่เจอ
while ($row = oci_fetch_array($stmt, 1)) { // 1 = OCI_ASSOC
    $results[] = [
        'kunnr' => trim($row['KUNNR']),
        'name1' => trim($row['NAME1'])
    ];
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode($results);
?>
