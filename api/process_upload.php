<?php
ob_start(); // เริ่มต้นดักจับ Output ทั้งหมด
ini_set('display_errors', 0); // ปิดการโชว์ Error เพื่อไม่ให้ไปกวน JSON
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
header('Content-Type: application/json');
session_start();
$createdBy = isset($_SESSION['user_code']) ? $_SESSION['user_code'] : 'SYSTEM';

/**
 * ฟังก์ชันจัดรูปแบบ Part Number
 * ถ้าเป็นตัวเลขทั้งหมด ให้เติม 0 ข้างหน้าให้ครบ 18 หลัก
 */
function formatPartNumber($partNo) {
    if (empty($partNo)) return '';
    $trimmed = trim($partNo);
    if (ctype_digit($trimmed)) {
        return str_pad($trimmed, 18, '0', STR_PAD_LEFT);
    }
    return $trimmed;
}

require_once '../vendor/SimpleXLSX.php';
// Include SimpleXLS if available for legacy .xls support
if (file_exists('../vendor/SimpleXLS.php')) {
    require_once '../vendor/SimpleXLS.php';
}
require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$customerCode = isset($_POST['customer']) ? trim($_POST['customer']) : '';
$plantFromPost = isset($_POST['plant']) ? trim($_POST['plant']) : ''; // ดักค่า plant จาก post ไว้ด้วย

if (!isset($_FILES['forecast_file']) || $_FILES['forecast_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
    exit;
}

$filePath = $_FILES['forecast_file']['tmp_name'];
$fileName = $_FILES['forecast_file']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$rows = null;
if ($ext === 'xlsx') {
    if ($xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
        $rows = $xlsx->rows();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to parse .xlsx file: ' . \Shuchkin\SimpleXLSX::parseError()]);
        exit;
    }
} else if ($ext === 'xls') {
    $xls = null;
    if (class_exists('\Shuchkin\SimpleXLS')) {
        $xls = \Shuchkin\SimpleXLS::parse($filePath);
    } else if (class_exists('SimpleXLS')) {
        $xls = SimpleXLS::parse($filePath);
    }
    
    if ($xls) {
        $rows = $xls->rows();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอ่านไฟล์ .xls ได้ (Library missing or parse error)']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported file format. Please use .xlsx or .xls']);
    exit;
}

if ($rows) {
    
    // 1. Extract ISSUE Date (Row 4)
    // Row 4 in SimpleXLSX is index 3
    $issueDateStr = '';
    $row4 = $rows[3] ?? [];
    foreach ($row4 as $cell) {
        if (stripos($cell, 'ISSUE Date') !== false) {
            // Regex to capture Date and Time: 2025-05-26 06:41
            if (preg_match('/(\d{4})[-](\d{2})[-](\d{2})\s+(\d{2}):(\d{2})/', $cell, $matches)) {
                // Combine into 202505260641
                $issueDateStr = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5];
            }
            break;
        }
    }

    if (empty($issueDateStr)) {
        foreach ($row4 as $cell) {
            if (preg_match('/(\d{4})[-](\d{2})[-](\d{2})\s+(\d{2}):(\d{2})/', $cell, $matches)) {
                $issueDateStr = $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5];
                break;
            }
        }
    }

    $dataToInsert = [];
    $currentPartNo = '';
    $currentPartName = '';
    $currentForecastType = ''; // DAY or WEEK
    $plant = $_POST['plant'] ?? 'N/A'; 

    // 2. Scan Rows from index 13 (Row 14)
    $rowCount = count($rows);
    for ($i = 13; $i < $rowCount; $i++) {
        $row = $rows[$i];
        
        // Search the whole row for keywords instead of just the first cell
        $rowString = implode(' ', array_map('strval', $row));

        // Check for Part NO. line
        if (stripos($rowString, 'Part NO.') !== false) {
            // Find which cell has Part NO.
            foreach ($row as $idx => $cell) {
                if (stripos($cell, 'Part NO.') !== false) {
                    // Try to extract from the same cell "Part NO. : 123"
                    if (preg_match('/Part NO\s*[:：]\s*([a-zA-Z0-9_-]+)/iu', $cell, $matches)) {
                        $currentPartNo = $matches[1];
                    } else {
                        // Look at next cell
                        $currentPartNo = trim($row[$idx+1] ?? '');
                    }
                    break;
                }
            }

            // Extract Part Name - search all cells in this row
            foreach($row as $idx => $cell) {
                if (stripos($cell, 'Part Name') !== false) {
                    if (preg_match('/Part Name\s*[:：]\s*(.+)/iu', $cell, $matches)) {
                        $currentPartName = trim($matches[1]);
                    } else {
                        $currentPartName = trim($row[$idx+1] ?? '');
                    }
                    break;
                }
            }
            continue;
        }

        // Check for Forecast Type
        if (stripos($rowString, 'Day Forecast') !== false) {
            $currentForecastType = 'D';
            continue;
        }
        if (stripos($rowString, 'Week Forecast') !== false) {
            $currentForecastType = 'W';
            continue;
        }

        // Check for Date and Qty pairs - search row for "Date"
        $dateIdx = -1;
        foreach ($row as $idx => $cell) {
            if (stripos(trim($cell), 'Date') === 0 || stripos(trim($cell), 'Date :') !== false) {
                $dateIdx = $idx;
                break;
            }
        }

        if ($dateIdx !== -1) {
            $dateRow = $row;
            $qtyRow = $rows[$i+1] ?? [];
            
            // Check if next row has "Qty" near the same index
            $hasQty = false;
            foreach ($qtyRow as $qidx => $qcell) {
                if (stripos(trim($qcell), 'Qty') === 0 || stripos(trim($qcell), 'Qty :') !== false) {
                    $hasQty = true;
                    break;
                }
            }

            if ($hasQty) {
                // Iterate through columns after the "Date :" label
                for ($j = $dateIdx + 1; $j < count($dateRow); $j++) {
                    $rawDate = trim($dateRow[$j] ?? '');
                    $qty = trim($qtyRow[$j] ?? '');

                    if (!empty($rawDate) && !empty($qty) && is_numeric(str_replace(',', '', $qty)) && (float)str_replace(',', '', $qty) > 0) {
                        $dataToInsert[] = [
                            'PART_NO' => formatPartNumber($currentPartNo),
                            'PART_NAME' => $currentPartName,
                            'TYPE' => $currentForecastType,
                            'DATE' => $rawDate,
                            'QTY' => (float)str_replace(',', '', $qty),
                            'ISSUE_DATE' => $issueDateStr
                        ];
                    }
                }
                $i++; // Skip the Qty row
            }
        }
    }

    if (empty($dataToInsert)) {
        $sample = '';
        if ($rowCount > 13) {
            $sample = "Row 14 first cell: '" . ($rows[13][0] ?? 'null') . "'";
        }
        echo json_encode(['status' => 'error', 'message' => 'No forecast data found. ' . $sample]);
        exit;
    }

    // 3. Oracle Interaction
    $conn = oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
    if (!$conn) {
        $e = oci_error();
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e['message']]);
        exit;
    }

    $insertSql = "INSERT INTO WEB.FC_CUST_FORECAST (
                FORECAST_ID, CUSTOMER_CODE, PLANT, PART_NO, PART_NAME, 
                FORECAST_TYPE, FORECAST_DATE, FORECAST_QTY, FORECAST_STATUS, 
                CUSTOMER_ISSUE_DATE, CREATED_BY
            ) VALUES (
                SYS_GUID(), :customer_code, :plant, :part_no, :part_name, 
                :f_type, :f_date, :f_qty, 'A', 
                :issue_date, :created_by
            )";

    $insStid = oci_parse($conn, $insertSql);
    
    // Pre-prepare a check statement to see if exact record exists
    $checkSql = "SELECT COUNT(*) as CNT FROM WEB.FC_CUST_FORECAST 
                 WHERE PLANT = :plant 
                   AND PART_NO = :part_no 
                   AND FORECAST_DATE = :f_date 
                   AND FORECAST_QTY = :f_qty 
                   AND FORECAST_STATUS = 'A'";
    $checkStid = oci_parse($conn, $checkSql);

    $successCount = 0;
    $skipCount = 0;
    $updateCount = 0;
    $newCount = 0;

    // Pre-prepare statements
    $checkExactSql = "SELECT COUNT(*) as CNT FROM WEB.FC_CUST_FORECAST 
                      WHERE PLANT = :plant AND PART_NO = :part_no 
                        AND FORECAST_DATE = :f_date AND FORECAST_QTY = :f_qty 
                        AND FORECAST_TYPE = :f_type AND FORECAST_STATUS = 'A'";
    $checkExactStid = oci_parse($conn, $checkExactSql);

    $checkExistsSql = "SELECT COUNT(*) as CNT FROM WEB.FC_CUST_FORECAST 
                       WHERE PLANT = :plant AND PART_NO = :part_no 
                         AND FORECAST_DATE = :f_date AND FORECAST_TYPE = :f_type AND FORECAST_STATUS = 'A'";
    $checkExistsStid = oci_parse($conn, $checkExistsSql);

    foreach ($dataToInsert as $item) {
        $rawDate = $item['DATE'];
        $cleanDate = '';

        // Date extraction (keep existing logic)
        if (strpos($rawDate, '/') !== false) {
            $parts = explode('/', $rawDate);
            if (count($parts) === 3) {
                $y = $parts[2]; $m = $parts[0]; $d = $parts[1];
                if (strlen($y) === 2) $y = '20' . $y;
                $cleanDate = sprintf('%04d%02d%02d', $y, $m, $d);
            }
        } 
        if (empty($cleanDate)) {
             $timestamp = strtotime($rawDate);
             if ($timestamp) $cleanDate = date('Ymd', $timestamp);
             else $cleanDate = preg_replace('/[^0-9]/', '', $rawDate);
        }
        if (strlen($cleanDate) !== 8) $cleanDate = preg_replace('/[^0-9]/', '', $rawDate);

        // 1. Check for EXACT duplicate
        oci_bind_by_name($checkExactStid, ':plant', $plant);
        oci_bind_by_name($checkExactStid, ':part_no', $item['PART_NO']);
        oci_bind_by_name($checkExactStid, ':f_date', $cleanDate);
        oci_bind_by_name($checkExactStid, ':f_qty', $item['QTY']);
        oci_bind_by_name($checkExactStid, ':f_type', $item['TYPE']);
        oci_execute($checkExactStid);
        $rowExact = oci_fetch_array($checkExactStid, OCI_ASSOC);
        
        if ($rowExact['CNT'] > 0) {
            $skipCount++;
            continue; 
        }

        // 2. Check if Part/Date exists (meaning Qty changed)
        oci_bind_by_name($checkExistsStid, ':plant', $plant);
        oci_bind_by_name($checkExistsStid, ':part_no', $item['PART_NO']);
        oci_bind_by_name($checkExistsStid, ':f_date', $cleanDate);
        oci_bind_by_name($checkExistsStid, ':f_type', $item['TYPE']);
        oci_execute($checkExistsStid);
        $rowExists = oci_fetch_array($checkExistsStid, OCI_ASSOC);
        
        if ($rowExists['CNT'] > 0) {
            $updateCount++;
            // Deactivate old
            $updSql = "UPDATE WEB.FC_CUST_FORECAST SET FORECAST_STATUS = 'X' 
                       WHERE PLANT = :plant AND PART_NO = :part_no AND FORECAST_DATE = :f_date 
                         AND FORECAST_TYPE = :f_type AND FORECAST_STATUS = 'A'";
            $updTmp = oci_parse($conn, $updSql);
            oci_bind_by_name($updTmp, ':plant', $plant);
            oci_bind_by_name($updTmp, ':part_no', $item['PART_NO']);
            oci_bind_by_name($updTmp, ':f_date', $cleanDate);
            oci_bind_by_name($updTmp, ':f_type', $item['TYPE']);
            oci_execute($updTmp, OCI_NO_AUTO_COMMIT);
            oci_free_statement($updTmp);
        } else {
            $newCount++;
        }

        // 3. Insert as Active
        oci_bind_by_name($insStid, ':plant', $plant);
        oci_bind_by_name($insStid, ':part_no', $item['PART_NO']);
        oci_bind_by_name($insStid, ':part_name', $item['PART_NAME']);
        oci_bind_by_name($insStid, ':f_type', $item['TYPE']);
        oci_bind_by_name($insStid, ':f_date', $cleanDate);
        oci_bind_by_name($insStid, ':f_qty', $item['QTY']);
        oci_bind_by_name($insStid, ':issue_date', $item['ISSUE_DATE']);
        oci_bind_by_name($insStid, ':created_by', $createdBy);
        $finalCustomer = !empty($customerCode) ? $customerCode : 'UNKNOWN';
        oci_bind_by_name($insStid, ':customer_code', $finalCustomer);


        if (oci_execute($insStid, OCI_NO_AUTO_COMMIT)) {
            $successCount++;
        }
    }
    
    oci_commit($conn);
    oci_free_statement($checkExactStid);
    oci_free_statement($checkExistsStid);
    oci_free_statement($insStid);
    oci_close($conn);
    
    ob_clean(); // ล้างข้อมูลขยะที่อาจจะเกิดขึ้นก่อนหน้านี้
    echo json_encode([
        'status' => 'success', 
        'details' => [
            'total' => $successCount + $skipCount,
            'new' => $newCount,
            'updated' => $updateCount,
            'duplicates' => $skipCount
        ],
        'message' => "Process Complete: $newCount New, $updateCount Updated, $skipCount Skipped."
    ]);

} else {
    // This part is handled by the extension logic above
}
