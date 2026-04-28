<?php
// tmp/final_check.php
header('Content-Type: text/plain; charset=utf-8');
require_once '../config/database.php';

echo "Database Check Tool\n";
echo "-------------------\n";

$conn = getDbConnection();
if (!$conn) {
    die("FAILED: Could not connect to SAGDB. Please check your credentials in config/database.php\n");
}
echo "SUCCESS: Connected to SAGDB\n\n";

$targetId = 'B167015';
echo "Searching for Employee: $targetId with privilege 'forecast'...\n";

$sql = "SELECT AUT_LEVEL, AUT_TYPE, AUT_NAME, AUT_PRIVILEGE, AUT_ACTIVE 
        FROM HRMSIT.HRMS_AUTH 
        WHERE TRIM(CODEMPID) = :id 
        AND UPPER(AUT_PRIVILEGE) = 'FORECAST'";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $targetId);

if (@oci_execute($stid)) {
    $found = false;
    while ($row = oci_fetch_array($stid, OCI_ASSOC | OCI_RETURN_NULLS)) {
        $found = true;
        echo "FOUND DATA:\n";
        echo "Level: " . $row['AUT_LEVEL'] . "\n";
        echo "Type: " . $row['AUT_TYPE'] . "\n";
        echo "Name (Raw): " . $row['AUT_NAME'] . "\n";
        echo "Name (UTF8): " . iconv('TIS-620', 'UTF-8//IGNORE', $row['AUT_NAME']) . "\n";
        echo "Status: " . $row['AUT_ACTIVE'] . "\n";
        echo "Privilege: " . $row['AUT_PRIVILEGE'] . "\n";
    }
    
    if (!$found) {
        echo "NOT FOUND: No record for $targetId with 'forecast' privilege in HRMSIT.HRMS_AUTH\n";
    }
} else {
    $e = oci_error($stid);
    echo "ERROR: Query failed - " . $e['message'] . "\n";
}

oci_close($conn);
?>
