<?php
// get_customers_ajax.php - AL32UTF8 Version
error_reporting(E_ALL);
ini_set('display_errors', 0); 

$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '';

if (empty($plant)) {
    echo "ERROR:กรุณาเลือก Plant";
    exit;
}

$user = "DSP";
$pwd  = "dsp";
$db   = "ECCERP";
$lang = "AL32UTF8"; // เปลี่ยนจาก WE8DEC เป็น AL32UTF8

$conn = @oci_connect($user, $pwd, $db, $lang);
if (!$conn) {
    $e = oci_error();
    echo "ERROR:เชื่อมต่อ SAP ไม่สำเร็จ: " . $e['message'];
    exit;
}

$sql = "SELECT t1.kunnr, t2.name1
        FROM sapsr3.VBAK t1
        JOIN sapsr3.KNA1 t2 ON t1.mandt = t2.mandt AND t1.kunnr = t2.kunnr
        WHERE t1.mandt = '400' AND t1.vkorg = :plant
        GROUP BY t1.kunnr, t2.name1
        ORDER BY t2.name1 ASC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':plant', $plant);

if (!@oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo "ERROR:Query ผิดพลาด: " . $e['message'];
    exit;
}

$results = [];
while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
    // เมื่อใช้ AL32UTF8 แล้ว ข้อมูลควรจะเป็น UTF-8 มาเลย ไม่ต้อง iconv
    $results[] = trim($row['KUNNR']) . "|||" . trim($row['NAME1']);
}

oci_free_statement($stmt);
oci_close($conn);

if (empty($results)) {
    echo "EMPTY:ไม่พบข้อมูลลูกค้า";
} else {
    echo implode("\n", $results);
}
?>
