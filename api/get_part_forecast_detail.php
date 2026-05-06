<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);
require_once '../config/conn.php';

$plant = isset($_GET['plant']) ? $_GET['plant'] : '';
$part_no = isset($_GET['part_no']) ? $_GET['part_no'] : '';

if (empty($plant)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing plant parameter']);
    exit;
}

/**
 * ฟังก์ชันจัดรูปแบบ Part Number ให้เป็น 18 หลักถ้าเป็นตัวเลขล้วน
 */
function formatPartNumber($partNo) {
    if (empty($partNo)) return '';
    $trimmed = trim($partNo);
    return ctype_digit($trimmed) ? str_pad($trimmed, 18, '0', STR_PAD_LEFT) : $trimmed;
}

$part_no = formatPartNumber($part_no);

$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    $parts_to_process = [];
    
    if (!empty($part_no)) {
        // Mode A: Single part
        $sql_info = "SELECT DISTINCT PART_NO, PART_NAME FROM WEB.FC_CUST_FORECAST WHERE PLANT = :plant AND PART_NO = :part_no AND FORECAST_STATUS = 'A' AND ROWNUM = 1";
        $stmt_info = oci_parse($conn, $sql_info);
        oci_bind_by_name($stmt_info, ':plant', $plant);
        oci_bind_by_name($stmt_info, ':part_no', $part_no);
        if (!@oci_execute($stmt_info)) {
            $e = oci_error($stmt_info);
            throw new Exception("Query failed (info): " . $e['message']);
        }
        $info = oci_fetch_array($stmt_info, OCI_ASSOC);
        if ($info) $parts_to_process[] = $info;
    } else {
        // Mode B: All parts for plant (Limit to top 50 for stability)
        $sql_all = "SELECT * FROM (SELECT DISTINCT PART_NO, PART_NAME FROM WEB.FC_CUST_FORECAST WHERE PLANT = :plant AND FORECAST_STATUS = 'A' ORDER BY PART_NO ASC) WHERE ROWNUM <= 50";
        $stmt_all = oci_parse($conn, $sql_all);
        oci_bind_by_name($stmt_all, ':plant', $plant);
        if (!@oci_execute($stmt_all)) {
            $e = oci_error($stmt_all);
            throw new Exception("Query failed (all): " . $e['message']);
        }
        while ($row = oci_fetch_array($stmt_all, OCI_ASSOC)) {
            $parts_to_process[] = $row;
        }
    }

    if (empty($parts_to_process)) {
        echo json_encode(['status' => 'error', 'message' => 'No data found for plant ' . $plant]);
        exit;
    }

    $final_results = [];

    foreach ($parts_to_process as $pinfo) {
        $curr_part = $pinfo['PART_NO'];
        
        // ตัดเลข 0 ออกเพื่อการแสดงผลที่สวยงาม
        if (ctype_digit($pinfo['PART_NO'])) {
            $pinfo['PART_NO'] = ltrim($pinfo['PART_NO'], '0') ?: '0';
        }
        
        // Fetch Daily
        $sql_d = "SELECT FORECAST_DATE as F_DATE, FORECAST_QTY as F_QTY FROM WEB.FC_CUST_FORECAST 
                  WHERE PLANT = :plant AND PART_NO = :pno AND FORECAST_TYPE = 'D' AND FORECAST_STATUS = 'A'
                  ORDER BY FORECAST_DATE ASC";
        $stmt_d = oci_parse($conn, $sql_d);
        oci_bind_by_name($stmt_d, ':plant', $plant);
        oci_bind_by_name($stmt_d, ':pno', $curr_part);
        if (!@oci_execute($stmt_d)) {
            $e = oci_error($stmt_d);
            throw new Exception("Query failed (daily): " . $e['message']);
        }
        $daily = [];
        while ($r = oci_fetch_array($stmt_d, OCI_ASSOC)) {
            if (strlen($r['F_DATE']) === 8) {
                $r['F_DATE'] = substr($r['F_DATE'], 0, 4) . '-' . substr($r['F_DATE'], 4, 2) . '-' . substr($r['F_DATE'], 6, 2);
            }
            $daily[] = $r;
        }

        // Fetch Weekly
        $sql_w = "SELECT FORECAST_DATE as F_DATE, FORECAST_QTY as F_QTY FROM WEB.FC_CUST_FORECAST 
                  WHERE PLANT = :plant AND PART_NO = :pno AND FORECAST_TYPE = 'W' AND FORECAST_STATUS = 'A'
                  ORDER BY FORECAST_DATE ASC";
        $stmt_w = oci_parse($conn, $sql_w);
        oci_bind_by_name($stmt_w, ':plant', $plant);
        oci_bind_by_name($stmt_w, ':pno', $curr_part);
        if (!@oci_execute($stmt_w)) {
            $e = oci_error($stmt_w);
            throw new Exception("Query failed (weekly): " . $e['message']);
        }
        $weekly = [];
        while ($r = oci_fetch_array($stmt_w, OCI_ASSOC)) {
            if (strlen($r['F_DATE']) === 8) {
                $r['F_DATE'] = substr($r['F_DATE'], 0, 4) . '-' . substr($r['F_DATE'], 4, 2) . '-' . substr($r['F_DATE'], 6, 2);
            }
            $weekly[] = $r;
        }

        $final_results[] = [
            'part_info' => $pinfo,
            'daily' => $daily,
            'weekly' => $weekly
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $final_results
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
