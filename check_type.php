<?php 
require_once 'config/conn.php';
$conn = oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
if (!$conn) { echo "FAIL"; exit; }
$sql = "SELECT data_type FROM all_tab_columns WHERE table_name = 'FC_FORECAST' AND owner = 'WEB' AND column_name = 'FORECAST_DATE'";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
$row = oci_fetch_array($stid, OCI_ASSOC);
echo "TYPE:" . ($row['DATA_TYPE'] ?? 'NOT FOUND');
?>
