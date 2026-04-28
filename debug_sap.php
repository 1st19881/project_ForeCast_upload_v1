<?php
// debug_sap.php - ตัวตรวจสอบแบบดิบๆ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Diagnostic Mode</h3>";

$user = "DSP";
$pwd  = "dsp";
$db   = "ECCERP";
$lang = "WE8DEC";

echo "Connecting to $db... ";
$conn = oci_connect($user, $pwd, $db, $lang);

if (!$conn) {
    $e = oci_error();
    die("<b style='color:red'>FAILED:</b> " . $e['message']);
}
echo "<b style='color:green'>SUCCESS!</b><br>";

$plant = isset($_GET['plant']) ? $_GET['plant'] : '1201';
echo "Searching for Plant: <b>$plant</b><br>";

$sql = "SELECT t1.kunnr, t2.name1
        FROM sapsr3.VBAK t1
        JOIN sapsr3.KNA1 t2 ON t1.mandt = t2.mandt AND t1.kunnr = t2.kunnr
        WHERE t1.mandt = '400' AND t1.vkorg = :plant
        GROUP BY t1.kunnr, t2.name1
        ORDER BY t2.name1 ASC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':plant', $plant);

echo "Executing Query... ";
if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    die("<b style='color:red'>QUERY FAILED:</b> " . $e['message']);
}
echo "<b style='color:green'>DONE!</b><br><hr>";

$count = 0;
echo "<ul>";
while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
    echo "<li>" . $row['KUNNR'] . " - " . $row['NAME1'] . "</li>";
    $count++;
}
echo "</ul>";
echo "Found: $count records.";

oci_free_statement($stmt);
oci_close($conn);
?>
