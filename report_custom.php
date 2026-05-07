<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['user_code'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/conn.php';

// Create connection
$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Database connection failed: " . $e['message']);
}

// Get Filter Values
$plant = isset($_GET['plant']) ? trim($_GET['plant']) : '1201';
$supplier = isset($_GET['supplier']) ? trim($_GET['supplier']) : '';
$part_no = isset($_GET['part_no']) ? trim($_GET['part_no']) : '';
$report_date = isset($_GET['report_date']) ? trim($_GET['report_date']) : date('Y-m-d');
$months = 6;

// Format dates for Oracle
$start_date = str_replace('-', '', $report_date);

// SQL 1: Header (Defining Columns)
$sql_header = "
select t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
from   
    (select * from z_bom_component t1
        where procure_type = 'F'
        and pur_grp between '101' and '110'
        and substr(t1.spec_procure,1,1) in ('2','3','4')
        ) t11,
     (select t02.*,decode(lifnr,'',lifnr_eord,lifnr) supplier  from z_eord_po t02) t22 ,
     (select t03.*
             ,substr(t03.forecast_date,1,4) fc_year,to_char(to_date(t03.forecast_date,'YYYYMMDD'), 'IW') as week_no
             ,case when t03.forecast_type = 'D' then t03.forecast_date 
             when t03.forecast_type = 'W' then to_char(trunc(to_date(t03.forecast_date,'YYYYMMDD'), 'IW'),'yyyymmdd') end  first_day_of_week
        from fc_cust_forecast t03
        where forecast_status = 'A' ) t33    
 where t11.part_no = t22.matnr
and t11.plant = t22.werks
and t11.plant = t33.plant
and t11.fg_part = t33.part_no
and t11.plant = :plant
and t22.supplier = :supplier
and t33.forecast_date between :start_date and to_char(add_months(to_date(:start_date,'YYYYMMDD'), :months) ,'YYYYMMDD')
" . ($part_no != '' ? "and (t11.fg_part = :part_no or t11.part_no = :part_no) " : "") . "
group by t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
order by t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
";

// SQL 2: Data (Rows and quantities)
$sql_data = "
select t11.plant,t11.part_no,t11.part_name,t11.model ,t22.supplier
       ,t33.forecast_type
       ,first_day_of_week
       ,sum(t33.forecast_qty*t11.fg_require_qty) consump_qty ,t11.unit
from  
    (select * from z_bom_component t01
        where procure_type = 'F'
        and pur_grp between '101' and '110'
        and substr(spec_procure,1,1) in ('2','3','4')
        ) t11,
     (select t02.*,decode(lifnr,'',lifnr_eord,lifnr) supplier  from z_eord_po t02) t22 ,
     (select t03.*
             ,substr(t03.forecast_date,1,4) fc_year,to_char(to_date(t03.forecast_date,'YYYYMMDD'), 'IW') as week_no
             ,case when t03.forecast_type = 'D' then t03.forecast_date 
             when t03.forecast_type = 'W' then to_char(trunc(to_date(t03.forecast_date,'YYYYMMDD'), 'IW'),'yyyymmdd') end  first_day_of_week
        from fc_cust_forecast t03
        where forecast_status = 'A' ) t33    
 where t11.part_no = t22.matnr
and t11.plant = t22.werks
and t11.plant = t33.plant
and t11.fg_part = t33.part_no
and t11.plant = :plant
and t22.supplier = :supplier
and t33.forecast_date between :start_date and to_char(add_months(to_date(:start_date,'YYYYMMDD'), :months) ,'YYYYMMDD')
" . ($part_no != '' ? "and (t11.fg_part = :part_no or t11.part_no = :part_no) " : "") . "
group by  t11.plant,t11.part_no,t11.part_name,t11.model ,t22.supplier
       ,t33.forecast_type
       ,first_day_of_week
       ,t11.unit
order by t11.part_no,t33.forecast_type,t33.first_day_of_week
";

function executeQuery($conn, $sql, $params = []) {
    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => $val) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "<div style='color:red;'>Error: " . $e['message'] . "</div>";
        return false;
    }
    $results = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $results[] = $row;
    }
    oci_free_statement($stmt);
    return $results;
}

// Helper to format date
function formatDate($ymd) {
    if (!$ymd) return '';
    $d = DateTime::createFromFormat('Ymd', $ymd);
    return $d ? $d->format('d-M') : $ymd;
}

// Helper to add days to date string
function addDaysYmd($ymd, $days) {
    $date = DateTime::createFromFormat('Ymd', $ymd);
    if (!$date) return '';
    $date->modify('+' . (int)$days . ' days');
    return $date->format('Ymd');
}

$params = [
    ':plant' => $plant,
    ':supplier' => $supplier,
    ':start_date' => $start_date,
    ':months' => $months
];
if ($part_no != '') {
    $params[':part_no'] = $part_no;
}

$periods = executeQuery($conn, $sql_header, $params);
$rawData = executeQuery($conn, $sql_data, $params);

oci_close($conn);

// Pivot Data
$rows = [];
$uniquePeriods = [];
foreach ($periods as $p) {
    $key = $p['FORECAST_TYPE'] . ':' . $p['FIRST_DAY_OF_WEEK'];
    $uniquePeriods[$key] = [
        'type' => $p['FORECAST_TYPE'],
        'date' => $p['FIRST_DAY_OF_WEEK'],
        'week' => $p['WEEK_NO'],
        'year' => $p['FC_YEAR'],
        'eta' => addDaysYmd($p['FIRST_DAY_OF_WEEK'], 66),
        'delivery_date' => addDaysYmd($p['FIRST_DAY_OF_WEEK'], 76)
    ];
}

foreach ($rawData as $d) {
    $partKey = $d['PART_NO'];
    if (!isset($rows[$partKey])) {
        $rows[$partKey] = [
            'PLANT' => $d['PLANT'],
            'PART_NO' => $d['PART_NO'],
            'PART_NAME' => $d['PART_NAME'],
            'MODEL' => $d['MODEL'],
            'UNIT' => $d['UNIT'],
            'quantities' => []
        ];
    }
    $periodKey = $d['FORECAST_TYPE'] . ':' . $d['FIRST_DAY_OF_WEEK'];
    $rows[$partKey]['quantities'][$periodKey] = $d['CONSUMP_QTY'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Report - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root { 
            --glass-bg: rgba(255,255,255,0.9); 
            --primary: #e30613; 
            --primary-dark: #b3050f; 
            --secondary: #64748b;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 100px;
        }
        
        body { 
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
            font-family: 'Inter', sans-serif; 
            color: #1e293b; 
            margin: 0; 
            padding: 0; 
            min-height: 100vh;
        }

        /* Use global main-wrapper from sidebar.css */
        
        .dashboard-header { margin-bottom: 2rem; }
        .dashboard-header h1 { 
            font-size: 2.2rem; 
            font-weight: 800; 
            background: linear-gradient(90deg, #1e293b 0%, var(--primary) 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            margin-bottom: 0.25rem;
            display: block;
            border-bottom: none;
        }

        /* Filter Card */
        .filter-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(10px); 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.8); 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); 
            padding: 1.5rem; 
            margin-bottom: 2rem; 
        }
        
        .form-label { font-weight: 600; font-size: 0.85rem; color: var(--secondary); margin-bottom: 0.5rem; }
        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 0.65rem 1rem; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc; 
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus { 
            box-shadow: 0 0 0 4px rgba(227,6,19,0.1); 
            border-color: var(--primary); 
        }

        .btn-filter { 
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            padding: 0; 
            height: 45px;
            font-weight: 600; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.15); 
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.02em;
        }
        .btn-filter:hover { 
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(30, 41, 59, 0.25); 
            color: white; 
        }

        .btn-reset { 
            background: #f1f5f9; 
            color: #475569; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 0; 
            height: 45px;
            font-weight: 600; 
            transition: all 0.3s; 
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .btn-reset:hover { background: #e2e8f0; color: #1e293b; }

        /* Report Table Styling */
        .report-card { 
            background: white; 
            border-radius: 20px; 
            border: 1px solid rgba(0,0,0,0.05); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            padding: 1.5rem; 
        }

        .table-wrap { 
            border: 1px solid #e2e8f0; 
            border-radius: 12px; 
            overflow: auto; 
            max-height: 70vh;
        }

        table { border-collapse: collapse; font-size: 11px; width: max-content; min-width: 100%; border: none; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: center; white-space: nowrap; }
        
        /* Header Styles */
        .fixed-head { background: #f1f5f9; font-weight: 700; color: #475569; }
        .fixed-head.light { background: #ffffff; }
        .row-label { background: #ffffff; text-align: left; font-weight: 700; color: #64748b; }
        
        .period-title-firm { background: #10b981; color: white; font-weight: 700; }
        .period-title-forecast { background: #f59e0b; color: white; font-weight: 700; }
        .period-date { background: #ecfdf5; color: #065f46; font-weight: 600; }
        .period-date-forecast { background: #fffbeb; color: #92400e; font-weight: 600; }
        
        .emergency-title { background: #10b981; color: white; font-weight: 700; }
        .emergency-fill { background: #fffbeb; }
        
        /* Body Styles */
        .part-name-cell { text-align: left; background: #fff; min-width: 200px; color: #1e293b; }
        .qty-cell { text-align: right; font-weight: 600; cursor: pointer; color: #0f172a; transition: all 0.2s; }
        .qty-cell:hover { background-color: #f0fdf4 !important; color: #166534; }
        tr:hover td { background-color: #f8fafc; }
        
        .no-data { padding: 60px; text-align: center; color: #94a3b8; }
        .no-data i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        /* Modal Styles */
        .modal-content { border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .modal-header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border-top-left-radius: 20px; border-top-right-radius: 20px; border-bottom: none; }
        .modal-footer { border-top: 1px solid #f1f5f9; }
        .drilldown-table { font-size: 12px; min-width: 1300px; }
        .drilldown-table th { background: #f8fafc; font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 10px; letter-spacing: 0.05em; white-space: nowrap; }
        .modal-body { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Loading Overlay */
        #loadingOverlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .spinner-box {
            width: 100px; height: 100px;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .spinner-outer {
            width: 80px; height: 80px;
            border: 4px solid transparent;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1.2s linear infinite;
        }
        .spinner-inner {
            width: 50px; height: 50px;
            border: 4px solid transparent;
            border-bottom-color: #1e293b;
            border-radius: 50%;
            position: absolute;
            animation: spin-reverse 0.8s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes spin-reverse { 0% { transform: rotate(0deg); } 100% { transform: rotate(-360deg); } }
        .loading-text {
            margin-top: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div id="loadingOverlay">
        <div class="spinner-box">
            <div class="spinner-outer"></div>
            <div class="spinner-inner"></div>
        </div>
        <div class="loading-text">Generating Report...</div>
    </div>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <header class="dashboard-header d-flex justify-content-between align-items-end">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-secondary small">Home</a></li>
                        <li class="breadcrumb-item active small" aria-current="page">Supplier Report</li>
                    </ol>
                </nav>
                <h1>Supplier Forecast Report</h1>
                <p class="text-secondary mb-0">Detailed forecast consumption by part and supplier</p>
            </div>
            <div class="text-end d-none d-md-block">
                <button class="btn btn-outline-success btn-sm rounded-pill px-3 me-2 shadow-sm" id="btnExport">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
                <span class="badge bg-white text-dark border p-2 rounded-3 shadow-sm">
                    <i class="fas fa-clock text-primary me-2"></i><?php echo date('d M Y, H:i'); ?>
                </span>
            </div>
        </header>
    
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="report_custom.php">
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Plant</label>
                        <select class="form-select" name="plant">
                            <option value="1101" <?php echo $plant == '1101' ? 'selected' : ''; ?>>1101</option>
                            <option value="1100" <?php echo $plant == '1100' ? 'selected' : ''; ?>>1100</option>
                            <option value="1202" <?php echo $plant == '1202' ? 'selected' : ''; ?>>1202</option>
                            <option value="1203" <?php echo $plant == '1203' ? 'selected' : ''; ?>>1203</option>
                            <option value="1201" <?php echo $plant == '1201' ? 'selected' : ''; ?>>1201</option>
                            <option value="1300" <?php echo $plant == '1300' ? 'selected' : ''; ?>>1300</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select class="form-select select2-supplier" id="filterSupplier" name="supplier">
                            <?php if ($supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier); ?>" selected><?php echo htmlspecialchars($supplier); ?></option>
                            <?php else: ?>
                                <option value="">Select Supplier</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Part Number</label>
                        <input type="text" class="form-control" name="part_no" value="<?php echo htmlspecialchars($part_no); ?>" placeholder="FG or Component">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="text" class="form-control" id="reportDate" name="report_date" value="<?php echo htmlspecialchars($report_date); ?>" readonly>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <div style="width: 160px;">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-sync-alt me-1"></i> Generate
                        </button>
                    </div>
                    <div style="width: 50px;">
                        <a href="report_custom.php" class="btn-reset" title="Reset Filters">
                            <i class="fas fa-redo-alt"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="report-card">
            <?php if (count($rows) > 0): ?>
            <div class="table-wrap">
                <table id="reportTable">
        <thead>
            <!-- Row 1: Titles -->
            <?php
            $firmCount = 0;
            $forecastCount = 0;
            foreach ($uniquePeriods as $p) {
                if ($p['type'] == 'D') $firmCount++; else $forecastCount++;
            }
            ?>
            <tr>
                <th rowspan="6" class="fixed-head">SA</th>
                <th rowspan="6" class="fixed-head">SA Item</th>
                <th rowspan="6" class="fixed-head">PART NO.</th>
                <th rowspan="6" class="fixed-head">PART NAME</th>
                <th rowspan="6" class="fixed-head light">MODEL</th>
                <th rowspan="6" class="fixed-head light">MPQ</th>
                <th rowspan="6" class="fixed-head light">WOQ</th>
                <th class="row-label"></th><!-- Empty cell above 'Order release date' -->
                <th class="emergency-title">Emergency Air</th>
                <th class="emergency-title">Emergency SEA</th>
                
                <?php if ($firmCount > 0): ?>
                    <th colspan="<?php echo $firmCount; ?>" class="period-title-firm">Firm Order</th>
                <?php endif; ?>
                
                <?php if ($forecastCount > 0): ?>
                    <th colspan="<?php echo $forecastCount; ?>" class="period-title-forecast">Forecast</th>
                <?php endif; ?>
            </tr>
            
            <!-- Row 2: Order release date -->
            <tr>
                <th class="row-label">Order release date</th>
                <th class="emergency-fill"></th>
                <th class="emergency-fill"></th>
                <?php foreach ($uniquePeriods as $p): ?>
                    <th class="<?php echo $p['type'] == 'D' ? 'period-date' : 'period-date-forecast'; ?>"><?php echo formatDate($p['date']); ?></th>
                <?php endforeach; ?>
            </tr>

            <!-- Row 3: Week Order -->
            <tr>
                <th class="row-label">Week order</th>
                <th class="emergency-fill"></th>
                <th class="emergency-fill"></th>
                <?php foreach ($uniquePeriods as $p): ?>
                    <th class="<?php echo $p['type'] == 'D' ? 'period-date' : 'period-date-forecast'; ?>">CW<?php echo (int)$p['week']; ?></th>
                <?php endforeach; ?>
            </tr>
            
            <!-- Row 4: ETD -->
            <tr>
                <th class="row-label">ETD</th>
                <th class="emergency-fill"></th>
                <th class="emergency-fill"></th>
                <?php foreach ($uniquePeriods as $p): ?>
                    <th class="<?php echo $p['type'] == 'D' ? 'period-date' : 'period-date-forecast'; ?>"></th>
                <?php endforeach; ?>
            </tr>
            
            <!-- Row 5: ETA -->
            <tr>
                <th class="row-label">ETA</th>
                <th class="emergency-fill"></th>
                <th class="emergency-fill"></th>
                <?php foreach ($uniquePeriods as $p): ?>
                    <th class="<?php echo $p['type'] == 'D' ? 'period-date' : 'period-date-forecast'; ?>"><?php echo formatDate($p['eta']); ?></th>
                <?php endforeach; ?>
            </tr>
            
            <!-- Row 6: Delivery date -->
            <tr>
                <th class="row-label">Delivery date</th>
                <th class="emergency-fill"></th>
                <th class="emergency-fill"></th>
                <?php foreach ($uniquePeriods as $p): ?>
                    <th class="<?php echo $p['type'] == 'D' ? 'period-date' : 'period-date-forecast'; ?>"><?php echo formatDate($p['delivery_date']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td></td><!-- SA -->
                <td></td><!-- SA Item -->
                <td><?php echo htmlspecialchars($r['PART_NO']); ?></td>
                <td class="part-name-cell"><?php echo htmlspecialchars($r['PART_NAME']); ?></td>
                <td><?php echo htmlspecialchars($r['MODEL']); ?></td>
                <td></td><!-- MPQ -->
                <td></td><!-- WOQ -->
                <td><?php echo htmlspecialchars($r['UNIT']); ?></td>
                <td></td><!-- Emergency Air -->
                <td></td><!-- Emergency SEA -->
                <?php foreach ($uniquePeriods as $key => $p): ?>
                    <td class="qty-cell" 
                        data-part-no="<?php echo htmlspecialchars($r['PART_NO']); ?>"
                        data-part-name="<?php echo htmlspecialchars($r['PART_NAME']); ?>"
                        data-plant="<?php echo htmlspecialchars($r['PLANT']); ?>"
                        data-supplier="<?php echo htmlspecialchars($supplier); ?>"
                        data-first-day="<?php echo htmlspecialchars($p['date']); ?>"
                        data-type="<?php echo htmlspecialchars($p['type']); ?>"
                        data-week="CW<?php echo (int)$p['week']; ?>"
                        data-fg-part="<?php echo htmlspecialchars($part_no); ?>">
                        <?php 
                        $qty = isset($r['quantities'][$key]) ? $r['quantities'][$key] : 0;
                        echo $qty > 0 ? number_format($qty, 2) : '0.00';
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
            </div><!-- End table-wrap -->
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-search-minus d-block"></i>
                    <p class="mb-0">No data found matching the report criteria.</p>
                    <small class="text-muted">Try adjusting your filters or date range.</small>
                </div>
            <?php endif; ?>
        </div><!-- End report-card -->
    </div><!-- End main-wrapper -->

<!-- Drilldown Modal -->
<div class="modal fade" id="drilldownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Consumption Drilldown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="drilldownContent">
                    <div class="p-4 text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading data...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
$(document).ready(function() {
    // Initialize Flatpickr date picker
    flatpickr('#reportDate', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-M-Y',
        defaultDate: '<?php echo $report_date; ?>',
        allowInput: false,
        disableMobile: true
    });
    // Show loading when generating report
    $('form').on('submit', function() {
        $('#loadingOverlay').css('display', 'flex').hide().fadeIn(300);
    });

    // Export Excel
    $('#btnExport').on('click', function() {
        const table = document.getElementById('reportTable');
        if (!table) return;

        const wb = XLSX.utils.table_to_book(table, { sheet: "Forecast Report" });
        const fileName = "Supplier_Forecast_Report_" + new Date().toISOString().slice(0, 10) + ".xlsx";
        XLSX.writeFile(wb, fileName);
    });

    // Initialize Select2 for Supplier
    const initSupplierSelect = (plant) => {
        $('#filterSupplier').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Supplier',
            allowClear: true,
            ajax: {
                url: 'api/get_suppliers.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        plant: plant,
                        q: params.term // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(item => ({
                            id: item.CODE,
                            text: item.CODE + ' - ' + item.NAME
                        }))
                    };
                },
                cache: true
            }
        });
    };

    // Initial load
    const currentPlant = $('select[name="plant"]').val();
    initSupplierSelect(currentPlant);

    // Update suppliers when plant changes
    $('select[name="plant"]').on('change', function() {
        const newPlant = $(this).val();
        $('#filterSupplier').val(null).trigger('change');
        initSupplierSelect(newPlant);
    });

    $('.qty-cell').on('click', function() {
        const data = $(this).data();
        const cellInfo = {
            plant: data.plant,
            supplier: data.supplier,
            partNo: data.partNo,
            partName: data.partName,
            firstDay: data.firstDay,
            week: data.week,
            type: data.type,
            fgPart: data.fgPart
        };

        $('#drilldownModal').modal('show');
        $('#drilldownContent').html('<div class="p-4 text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Loading data...</p></div>');

        $.ajax({
            url: 'api/get_supplier_report_drilldown.php',
            type: 'GET',
            data: {
                plant: data.plant,
                supplier: data.supplier,
                part_no: data.partNo,
                fg_part: data.fgPart,
                first_day_of_week: data.firstDay,
                forecast_type: data.type
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderDrilldown(response.data, response.summary, cellInfo);
                } else {
                    $('#drilldownContent').html('<div class="alert alert-danger m-3">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#drilldownContent').html('<div class="alert alert-danger m-3">Failed to fetch data from server.</div>');
            }
        });
    });

    function formatDateJS(ymd) {
        if (!ymd || ymd.toString().length !== 8) return ymd;
        const s = ymd.toString();
        const year = s.substring(0, 4);
        const month = s.substring(4, 6);
        const day = s.substring(6, 8);
        return day + '/' + month + '/' + year;
    }

    function trimZerosJS(str) {
        if (!str) return '';
        return str.toString().replace(/^0+/, '');
    }

    function renderDrilldown(data, summary, info) {
        if (data.length === 0) {
            $('#drilldownContent').html('<div class="p-4 text-center text-muted">No details found for this period.</div>');
            return;
        }

        const typeBadge = info.type === 'D' 
            ? '<span class="badge bg-info" style="font-size:0.75rem;">Daily</span>' 
            : '<span class="badge bg-warning text-dark" style="font-size:0.75rem;">Weekly</span>';

        const formattedFCDate = formatDateJS(info.firstDay);

        // Detail summary section - Vertical Left-aligned
        let html = '<div class="p-3" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:0.85rem;">';
        html += '<div class="row">';
        
        // Left Column
        html += '<div class="col-md-6 border-end">';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Plant:</strong> ' + info.plant + '</div>';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Supplier:</strong> ' + trimZerosJS(info.supplier) + '</div>';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Part No:</strong> ' + trimZerosJS(info.partNo) + '</div>';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Part Name:</strong> ' + (info.partName || '-') + '</div>';
        html += '</div>';
        
        // Right Column
        html += '<div class="col-md-6 ps-4">';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">FC Date:</strong> <span class="text-primary fw-bold">' + formattedFCDate + '</span></div>';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Week:</strong> ' + info.week + '</div>';
        html += '<div class="mb-1"><strong class="text-secondary" style="width:80px; display:inline-block;">Type:</strong> ' + typeBadge + '</div>';
        html += '</div>';
        
        html += '</div></div>';

        // Table with specific column widths to prevent stretching
        html += '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0" style="font-size:0.85rem; table-layout: fixed; width: 100%;">';
        html += '<thead class="table-dark"><tr>';
        html += '<th style="width: 15%;">FG Part No</th>';
        html += '<th style="width: 45%;">FG Part Name</th>';
        html += '<th style="width: 13%;" class="text-end">FC Qty</th>';
        html += '<th style="width: 12%;" class="text-end">Usage</th>';
        html += '<th style="width: 15%;" class="text-end">Consump</th>';
        html += '</tr></thead><tbody>';

        data.forEach(function(row) {
            html += '<tr>';
            html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + trimZerosJS(row.FG_PART_NO) + '</td>';
            html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + row.FG_PART_NAME + '">' + row.FG_PART_NAME + '</td>';
            html += '<td class="text-end">' + row.FORECAST_QTY.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + '</td>';
            html += '<td class="text-end">' + row.USAGE_QTY.toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4}) + '</td>';
            html += '<td class="text-end fw-bold">' + row.CONSUMP_QTY.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
            html += '</tr>';
        });

        html += '</tbody>';
        html += '<tfoot class="table-light"><tr>';
        html += '<td colspan="2" class="text-end fw-bold">Total</td>';
        html += '<td class="text-end fw-bold">' + summary.total_forecast_qty.toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) + '</td>';
        html += '<td></td>';
        html += '<td class="text-end fw-bold text-danger">' + summary.total_consump_qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
        html += '</tr></tfoot>';
        html += '</table></div>';

        $('#drilldownContent').html(html);
    }
});
</script>

</body>
</html>
