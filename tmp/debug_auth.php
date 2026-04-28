<?php
// tmp/debug_auth.php
header('Content-Type: text/plain; charset=utf-8');
require_once '../config/conn.php';

echo "Database: $SagDB\n";
echo "User: $SagUser\n";
echo "Lang: WE8DEC\n\n";

$conn = oci_connect($SagUser, $SagPWD, $SagDB, 'WE8DEC');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

echo "Testing access to HRMSIT.HRMS_AUTH...\n";

// 1. Try to count total records in that table
$sql = "SELECT COUNT(*) FROM HRMSIT.HRMS_AUTH";
$stid = oci_parse($conn, $sql);
if (@oci_execute($stid)) {
    $row = oci_fetch_array($stid);
    echo "Total records in table: " . $row[0] . "\n";
} else {
    $e = oci_error($stid);
    echo "Access Denied to table: " . $e['message'] . "\n";
}

// 2. Search for B167015
echo "\nSearching for B167015...\n";
$targetId = 'B167015';
$sql = "SELECT * FROM HRMSIT.HRMS_AUTH WHERE TRIM(CODEMPID) = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $targetId);
oci_execute($stid);

$found = false;
while ($row = oci_fetch_array($stid, OCI_ASSOC | OCI_RETURN_NULLS)) {
    $found = true;
    print_r($row);
}

if (!$found) {
    echo "B167015 NOT FOUND in this database using this user.\n";
}

oci_close($conn);
?>
