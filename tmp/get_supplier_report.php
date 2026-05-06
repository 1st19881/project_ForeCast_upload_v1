<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../config/conn.php';

$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '';
$supplier = isset($_GET['supplier']) ? trim($_GET['supplier']) : '';
$part_no = isset($_GET['part_no']) ? trim($_GET['part_no']) : '';
$report_date = isset($_GET['report_date']) ? trim($_GET['report_date']) : date('Y-m-d');
$months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
$months = max(1, min($months, 12));

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

function displayDateFromYmd($ymd) {
    $date = DateTime::createFromFormat('Ymd', $ymd);
    return $date ? $date->format('j-M-Y') : $ymd;
}

function addDaysYmd($ymd, $days) {
    $date = DateTime::createFromFormat('Ymd', $ymd);
    if (!$date) return '';
    $date->modify('+' . (int)$days . ' days');
    return $date->format('j-M-Y');
}

function isoWeekFromYmd($ymd) {
    $date = DateTime::createFromFormat('Ymd', $ymd);
    return $date ? (int)$date->format('W') : 0;
}

function normalizeReportDate($reportDate) {
    $date = DateTime::createFromFormat('Y-m-d', $reportDate);
    if (!$date) {
        $date = DateTime::createFromFormat('Ymd', $reportDate);
    }
    if (!$date) {
        $date = new DateTime();
    }
    return $date->format('Ymd');
}

function addMonthsYmd($ymd, $months) {
    $date = DateTime::createFromFormat('Ymd', $ymd);
    if (!$date) return $ymd;
    $date->modify('+' . (int)$months . ' months');
    return $date->format('Ymd');
}

function buildBaseFromSql() {
    return "FROM
            (SELECT *
               FROM z_bom_component t01
              WHERE procure_type = 'F'
                AND pur_grp BETWEEN '101' AND '110'
                AND SUBSTR(spec_procure, 1, 1) IN ('2', '3', '4')) t11,
            (SELECT t02.*, DECODE(lifnr, '', lifnr_eord, lifnr) supplier
               FROM z_eord_po t02) t22,
            (SELECT t03.*,
                    SUBSTR(t03.forecast_date, 1, 4) fc_year,
                    TO_CHAR(TO_DATE(t03.forecast_date, 'YYYYMMDD'), 'IW') week_no,
                    CASE
                        WHEN t03.forecast_type = 'D' THEN t03.forecast_date
                        WHEN t03.forecast_type = 'W' THEN TO_CHAR(TRUNC(TO_DATE(t03.forecast_date, 'YYYYMMDD'), 'IW'), 'YYYYMMDD')
                    END first_day_of_week
               FROM fc_cust_forecast t03
              WHERE forecast_status = 'A') t33";
}

function buildBaseWhere($plant, $supplier, $partNo, &$params) {
    $where = "WHERE t11.part_no = t22.matnr
                AND t11.plant = t22.werks
                AND t11.plant = t33.plant
                AND t11.fg_part = t33.part_no
                AND t33.forecast_date BETWEEN :start_date AND :end_date";

    if ($plant !== '') {
        $where .= " AND t11.plant = :plant";
        $params[':plant'] = $plant;
    }

    if ($supplier !== '') {
        $where .= " AND t22.supplier = :supplier";
        $params[':supplier'] = $supplier;
    }

    if ($partNo !== '') {
        $where .= " AND (t11.fg_part = :part_no OR t11.part_no = :part_no)";
        $params[':part_no'] = $partNo;
    }

    return $where;
}

function bindParams($stmt, &$params) {
    foreach ($params as $key => $value) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }
}

function makePeriod($index, $forecastType, $firstDayOfWeek, $weekNo) {
    return [
        'key' => 'P' . $index,
        'source_key' => $forecastType . ':' . $firstDayOfWeek,
        'forecast_type' => $forecastType,
        'bucket' => $index === 0 ? 'firm' : 'forecast',
        'title' => $index === 0 ? 'Firm Order' : 'Forecast',
        'order_release_date' => displayDateFromYmd($firstDayOfWeek),
        'week_order' => 'CW' . (int)$weekNo,
        'etd' => '',
        'eta' => addDaysYmd($firstDayOfWeek, 66),
        'delivery_date' => addDaysYmd($firstDayOfWeek, 76),
        'first_day_of_week' => $firstDayOfWeek
    ];
}

function buildFallbackPeriods($startDate, $endDate) {
    $periods = [];
    $periods[] = makePeriod(0, 'D', $startDate, isoWeekFromYmd($startDate));

    $weekStart = DateTime::createFromFormat('Ymd', $startDate);
    $end = DateTime::createFromFormat('Ymd', $endDate);
    if (!$weekStart || !$end) {
        return $periods;
    }

    $weekStart->modify('monday this week');
    while ($weekStart <= $end) {
        $ymd = $weekStart->format('Ymd');
        $periods[] = makePeriod(count($periods), 'W', $ymd, (int)$weekStart->format('W'));
        $weekStart->modify('+7 days');
    }

    return $periods;
}

function fetchPeriods($conn, $plant, $supplier, $partNo, $startDate, $endDate) {
    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];

    $baseFrom = buildBaseFromSql();
    $baseWhere = buildBaseWhere($plant, $supplier, $partNo, $params);

    $sql = "SELECT t22.supplier,
                   t33.forecast_type,
                   SUBSTR(t33.forecast_date, 1, 4) fc_year,
                   TO_CHAR(TO_DATE(t33.forecast_date, 'YYYYMMDD'), 'IW') week_no,
                   t33.first_day_of_week
              $baseFrom
              $baseWhere
             GROUP BY t22.supplier,
                      t33.forecast_type,
                      SUBSTR(t33.forecast_date, 1, 4),
                      TO_CHAR(TO_DATE(t33.forecast_date, 'YYYYMMDD'), 'IW'),
                      t33.first_day_of_week
             ORDER BY t22.supplier,
                      t33.forecast_type,
                      SUBSTR(t33.forecast_date, 1, 4),
                      TO_CHAR(TO_DATE(t33.forecast_date, 'YYYYMMDD'), 'IW'),
                      t33.first_day_of_week";

    $stmt = oci_parse($conn, $sql);
    bindParams($stmt, $params);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        throw new Exception('Period query failed' . (isset($e['message']) ? ': ' . $e['message'] : ''));
    }

    $periods = [];
    $tempRows = [];
    $dailyWeeks = [];
    $seenUnique = [];

    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS)) {
        if (empty($row['FIRST_DAY_OF_WEEK'])) {
            continue;
        }

        $uniqueKey = $row['FORECAST_TYPE'] . ':' . $row['FIRST_DAY_OF_WEEK'];
        if (isset($seenUnique[$uniqueKey])) {
            continue;
        }
        $seenUnique[$uniqueKey] = true;

        if ($row['FORECAST_TYPE'] === 'D') {
            $dailyWeeks[$row['FC_YEAR'] . '-' . $row['WEEK_NO']] = true;
        }
        $tempRows[] = $row;
    }

    foreach ($tempRows as $row) {
        $weekKey = $row['FC_YEAR'] . '-' . $row['WEEK_NO'];
        if ($row['FORECAST_TYPE'] === 'W' && isset($dailyWeeks[$weekKey])) {
            continue;
        }

        $index = count($periods);
        $periods[] = makePeriod($index, $row['FORECAST_TYPE'], $row['FIRST_DAY_OF_WEEK'], $row['WEEK_NO']);
    }

    oci_free_statement($stmt);
    return $periods;
}

function fetchRows($conn, $baseFrom, $baseWhere, &$params, $periods) {
    if (empty($periods)) {
        return [];
    }

    $qtySelects = [];
    foreach ($periods as $index => $period) {
        $typeBind = ':period_type_' . $index;
        $dayBind = ':period_day_' . $index;
        $params[$typeBind] = $period['forecast_type'];
        $params[$dayBind] = $period['first_day_of_week'];
        $qtySelects[] = "SUM(CASE
                              WHEN t33.forecast_type = $typeBind
                               AND t33.first_day_of_week = $dayBind
                              THEN (NVL(t33.forecast_qty, 0) * NVL(t11.fg_require_qty, 1))
                              ELSE 0
                           END) AS QTY_" . $index;
    }

    $sql = "SELECT *
              FROM (
                    SELECT t11.plant AS plant,
                           t11.fg_part AS fg_part,
                           t11.part_no AS part_no,
                           t11.part_name AS part_name,
                           t11.model AS model,
                           t11.unit AS unit,
                           t22.supplier AS supplier,
                           " . implode(",\n                           ", $qtySelects) . "
                      $baseFrom
                      $baseWhere
                     GROUP BY t11.plant,
                              t11.fg_part,
                              t11.part_no,
                              t11.part_name,
                              t11.model,
                              t11.unit,
                              t22.supplier
                     ORDER BY t11.part_no,
                              t11.fg_part
                   )
             WHERE ROWNUM <= 1000";

    $stmt = oci_parse($conn, $sql);
    bindParams($stmt, $params);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        throw new Exception('Data query failed' . (isset($e['message']) ? ': ' . $e['message'] : ''));
    }

    $results = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC | OCI_RETURN_NULLS)) {
        $quantities = [];
        foreach ($periods as $index => $period) {
            $qty = isset($row['QTY_' . $index]) ? $row['QTY_' . $index] : 0;
            $quantities[$period['key']] = (float)$qty;
        }

        $results[] = [
            'SA' => '',
            'SA_ITEM' => '',
            'PART_NO' => displayPartNumber($row['PART_NO']),
            'PART_NO_RAW' => $row['PART_NO'],
            'PART_NAME' => safeUtf8($row['PART_NAME']),
            'FG_PART' => displayPartNumber($row['FG_PART']),
            'FG_PART_RAW' => $row['FG_PART'],
            'MODEL' => safeUtf8($row['MODEL']),
            'MPQ' => '',
            'WOQ' => '',
            'UOM' => safeUtf8($row['UNIT']),
            'EMERGENCY_AIR' => '',
            'EMERGENCY_SEA' => '',
            'PLANT' => $row['PLANT'],
            'SUPPLIER' => $row['SUPPLIER'],
            'quantities' => $quantities
        ];
    }

    oci_free_statement($stmt);
    return $results;
}

$startDate = normalizeReportDate($report_date);
$endDate = addMonthsYmd($startDate, $months);
$part_no = normalizePartNumber($part_no);

$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed' . (isset($e['message']) ? ': ' . $e['message'] : '')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ];

    $baseFrom = buildBaseFromSql();
    $baseWhere = buildBaseWhere($plant, $supplier, $part_no, $params);
    $periods = fetchPeriods($conn, $plant, $supplier, $part_no, $startDate, $endDate);
    if (empty($periods)) {
        $periods = buildFallbackPeriods($startDate, $endDate);
    }
    $rows = fetchRows($conn, $baseFrom, $baseWhere, $params, $periods);

    echo json_encode([
        'status' => 'success',
        'periods' => $periods,
        'data' => $rows,
        'filters_applied' => [
            'plant' => $plant,
            'supplier' => $supplier,
            'part_no' => $part_no,
            'report_date' => $report_date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'months' => $months
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    oci_close($conn);
}
