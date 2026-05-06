<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../config/conn.php';

$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '';
$supplier = isset($_GET['supplier']) ? trim($_GET['supplier']) : '';
$fg_part = isset($_GET['fg_part']) ? trim($_GET['fg_part']) : '';
$part_no = isset($_GET['part_no']) ? trim($_GET['part_no']) : '';
$forecast_type = isset($_GET['forecast_type']) ? trim($_GET['forecast_type']) : '';
$first_day_of_week = isset($_GET['first_day_of_week']) ? trim($_GET['first_day_of_week']) : '';

function normalizePartNumber($partNo) {
    if ($partNo === '') return '';
    return ctype_digit($partNo) ? str_pad($partNo, 18, '0', STR_PAD_LEFT) : strtoupper($partNo);
}

function displayPartNumber($partNo) {
    if ($partNo !== null && ctype_digit($partNo)) {
        return ltrim($partNo, '0') ?: '0';
    }
    return $partNo;
}

function safeUtf8($value) {
    if ($value === null) return '';
    return trim($value);
}

$fg_part = normalizePartNumber($fg_part);
$part_no = normalizePartNumber($part_no);

$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $params = [];
    $where = "WHERE t11.part_no = t22.matnr
                AND t11.plant = t22.werks
                AND t11.plant = t33.plant
                AND t11.fg_part = t33.part_no";

    if ($plant !== '') {
        $where .= " AND t11.plant = :plant";
        $params[':plant'] = $plant;
    }
    if ($supplier !== '') {
        $where .= " AND t22.supplier = :supplier";
        $params[':supplier'] = $supplier;
    }
    if ($part_no !== '') {
        $where .= " AND t11.part_no = :part_no";
        $params[':part_no'] = $part_no;
    }
    if ($fg_part !== '') {
        $where .= " AND t11.fg_part = :fg_part";
        $params[':fg_part'] = $fg_part;
    }
    if ($first_day_of_week !== '') {
        $where .= " AND (CASE 
                            WHEN t33.forecast_type = 'D' THEN t33.forecast_date 
                            WHEN t33.forecast_type = 'W' THEN TO_CHAR(TRUNC(TO_DATE(t33.forecast_date, 'YYYYMMDD'), 'IW'), 'YYYYMMDD') 
                         END) = :first_day_of_week";
        $params[':first_day_of_week'] = $first_day_of_week;
    }

    $sql = "SELECT t11.plant, 
                   t33.part_no AS fg_part_no, 
                   t33.part_name AS fg_part_name,
                   t33.forecast_type, 
                   t33.forecast_date, 
                   (CASE 
                        WHEN t33.forecast_type = 'D' THEN t33.forecast_date 
                        WHEN t33.forecast_type = 'W' THEN TO_CHAR(TRUNC(TO_DATE(t33.forecast_date, 'YYYYMMDD'), 'IW'), 'YYYYMMDD') 
                    END) AS first_day_of_week,
                   t22.supplier, 
                   t11.part_no, 
                   t11.part_name,
                   t33.forecast_qty, 
                   t11.fg_require_qty AS usage_qty,
                   (NVL(t33.forecast_qty, 0) * NVL(t11.fg_require_qty, 1)) AS consump_qty
              FROM (SELECT * FROM z_bom_component 
                     WHERE procure_type = 'F' 
                       AND pur_grp BETWEEN '101' AND '110' 
                       AND SUBSTR(spec_procure, 1, 1) IN ('2', '3', '4')) t11,
                   (SELECT t02.*, DECODE(lifnr, '', lifnr_eord, lifnr) AS supplier FROM z_eord_po t02) t22,
                   (SELECT t03.* FROM fc_cust_forecast t03 WHERE forecast_status = 'A') t33
              $where
              ORDER BY t33.forecast_date, t33.part_no";

    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => $val) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        throw new Exception('Drilldown query failed: ' . $e['message']);
    }

    $data = [];
    $totalForecast = 0;
    $totalConsump = 0;

    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS)) {
        $totalForecast += (float)$row['FORECAST_QTY'];
        $totalConsump += (float)$row['CONSUMP_QTY'];

        $data[] = [
            'PLANT' => $row['PLANT'],
            'FG_PART_NO' => displayPartNumber($row['FG_PART_NO']),
            'FG_PART_NAME' => safeUtf8($row['FG_PART_NAME']),
            'FORECAST_TYPE' => $row['FORECAST_TYPE'],
            'FORECAST_DATE' => $row['FORECAST_DATE'],
            'FIRST_DAY_OF_WEEK' => $row['FIRST_DAY_OF_WEEK'],
            'SUPPLIER' => $row['SUPPLIER'],
            'PART_NO' => displayPartNumber($row['PART_NO']),
            'PART_NAME' => safeUtf8($row['PART_NAME']),
            'FORECAST_QTY' => (float)$row['FORECAST_QTY'],
            'USAGE_QTY' => (float)$row['USAGE_QTY'],
            'CONSUMP_QTY' => (float)$row['CONSUMP_QTY']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'summary' => [
            'rows' => count($data),
            'total_forecast_qty' => $totalForecast,
            'total_consump_qty' => $totalConsump
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    oci_close($conn);
}
