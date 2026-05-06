<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../config/conn.php';

$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '';
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    $params = [];
    $where = "WHERE 1=1";

    if ($plant !== '') {
        $where .= " AND t1.werks = :plant";
        $params[':plant'] = $plant;
    }

    if ($query !== '') {
        $where .= " AND (t1.lifnr LIKE :q OR t2.name1 LIKE :q)";
        $params[':q'] = '%' . strtoupper($query) . '%';
    }

    // Query suppliers from EORD (Source List) joined with LFA1 (Master)
    $sql = "SELECT DISTINCT 
                t1.lifnr AS CODE, 
                t2.name1 AS NAME
            FROM 
                z_eord_po t1
            LEFT JOIN 
                LFA1 t2 ON t1.lifnr = t2.lifnr
            $where
            ORDER BY t1.lifnr ASC";

    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => $val) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        throw new Exception($e['message']);
    }

    $results = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS)) {
        $results[] = [
            'CODE' => trim($row['CODE']),
            'NAME' => trim($row['NAME'])
        ];
    }

    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    oci_close($conn);
}
