<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../config/conn.php';

$plant = $_GET['plant'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit + 1;
$end = $page * $limit;

$conn = oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
if (!$conn) {
    $e = oci_error();
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e['message']]);
    exit;
}

$where = "WHERE FORECAST_STATUS = 'A'";
$params = [];

if (!empty($plant)) {
    $where .= " AND PLANT = :plant";
    $params[':plant'] = $plant;
}

if (!empty($search)) {
    $where .= " AND (PART_NO LIKE :search OR PART_NAME LIKE :search)";
    $params[':search'] = "%$search%";
}

// 1. Get Total Count for Pagination
$countSql = "SELECT COUNT(*) as TOTAL FROM WEB.FC_CUST_FORECAST $where";
$countStid = oci_parse($conn, $countSql);
foreach ($params as $key => $val) {
    oci_bind_by_name($countStid, $key, $params[$key]);
}
oci_execute($countStid);
$countRow = oci_fetch_array($countStid, OCI_ASSOC);
$totalRecords = $countRow['TOTAL'];

// 2. Get Paginated Data
$sql = "SELECT * FROM (
            SELECT a.*, ROWNUM rnum FROM (
                SELECT 
                    FORECAST_ID, PLANT, PART_NO, PART_NAME, 
                    FORECAST_TYPE, 
                    SUBSTR(FORECAST_DATE, 7, 2) || '/' || SUBSTR(FORECAST_DATE, 5, 2) || '/' || SUBSTR(FORECAST_DATE, 1, 4) as FORECAST_DATE_DISPLAY, 
                    FORECAST_QTY, 
                    CUSTOMER_ISSUE_DATE, 
                    TO_CHAR(CREATED_AT, 'DD/MM/YYYY HH24:MI:SS') as CREATED_AT
                FROM WEB.FC_CUST_FORECAST
                $where
                ORDER BY PART_NO ASC, FORECAST_DATE ASC
            ) a WHERE ROWNUM <= :end_row
        ) WHERE rnum >= :start_row";

$stid = oci_parse($conn, $sql);
foreach ($params as $key => $val) {
    oci_bind_by_name($stid, $key, $params[$key]);
}
oci_bind_by_name($stid, ':start_row', $start);
oci_bind_by_name($stid, ':end_row', $end);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . $e['message']]);
    exit;
}

$data = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC | OCI_RETURN_NULLS)) {
    if (isset($row['PART_NAME'])) {
        $row['PART_NAME'] = iconv('TIS-620', 'UTF-8//IGNORE', $row['PART_NAME']);
    }
    // ตัดเลข 0 ข้างหน้าออกเพื่อความสวยงามตอนแสดงผล
    if (isset($row['PART_NO']) && ctype_digit($row['PART_NO'])) {
        $row['PART_NO'] = ltrim($row['PART_NO'], '0') ?: '0';
    }
    $data[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $data,
    'pagination' => [
        'total' => (int)$totalRecords,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalRecords / $limit)
    ]
]);

oci_free_statement($stid);
oci_free_statement($countStid);
oci_close($conn);
