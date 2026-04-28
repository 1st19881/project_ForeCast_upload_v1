<?php
header('Content-Type: application/json');
require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$plant = $_POST['plant'] ?? '';

if (empty($plant)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a plant to clear data.']);
    exit;
}

$conn = oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
if (!$conn) {
    $e = oci_error();
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e['message']]);
    exit;
}

// Physically delete records for the selected plant
$deleteSql = "DELETE FROM WEB.FC_CUST_FORECAST WHERE PLANT = :plant";
$stid = oci_parse($conn, $deleteSql);
oci_bind_by_name($stid, ':plant', $plant);

if (oci_execute($stid, OCI_COMMIT_ON_SUCCESS)) {
    $count = oci_num_rows($stid);
    echo json_encode(['status' => 'success', 'message' => "Successfully cleared $count records for Plant $plant."]);
} else {
    $e = oci_error($stid);
    echo json_encode(['status' => 'error', 'message' => 'Clear operation failed: ' . $e['message']]);
}

oci_free_statement($stid);
oci_close($conn);
