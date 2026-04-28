<?php
require_once '../config/conn.php';
$conn = oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
$sql = "SELECT PLANT, COUNT(*) as TOTAL FROM FORECAST_SAIC GROUP BY PLANT";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
echo "Current Plant data in DB:\n";
while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
    echo $row['PLANT'] . ": " . $row['TOTAL'] . " rows\n";
}
oci_close($conn);
?>
