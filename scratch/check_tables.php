<?php
require_once 'config/conn.php';
$conn = oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

$tables = ['FC_CUST_FORECAST', 'WEB.FC_CUST_FORECAST', 'FC_CUS_FORECAST'];

foreach ($tables as $table) {
    $sql = "SELECT count(*) FROM $table WHERE ROWNUM = 1";
    $stmt = oci_parse($conn, $sql);
    if (@oci_execute($stmt)) {
        echo "$table: OK\n";
    } else {
        $e = oci_error($stmt);
        echo "$table: FAIL (" . $e['message'] . ")\n";
    }
}
oci_close($conn);
