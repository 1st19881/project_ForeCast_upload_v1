<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['user_code'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outbound to Supplier Report - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.9);
            --primary: #e30613;
            --primary-dark: #b3050f;
            --secondary: #64748b;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .main-wrapper {
            padding: 2.5rem;
            min-height: 100vh;
        }

        .content-container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Dashboard Header */
        .dashboard-header {
            margin-bottom: 2rem;
        }
        .dashboard-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(90deg, #1e293b 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        /* Filter Card */
        .filter-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.8);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(227, 6, 19, 0.1);
            border-color: var(--primary);
        }

        /* Report Card */
        .report-card {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            padding: 1.5rem;
            overflow: hidden;
        }

        /* Custom Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table thead th {
            border: none;
            background: #f8fafc;
            color: var(--secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem;
        }

        .table tbody tr {
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }

        .table tbody tr:hover {
            transform: scale(1.002);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background-color: #f8fafc !important;
        }

        .table tbody td {
            background: white;
            border: none;
            padding: 1.25rem 1rem;
            vertical-align: middle;
        }

        .table tbody td:first-child { border-radius: 12px 0 0 12px; }
        .table tbody td:last-child { border-radius: 0 12px 12px 0; }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.65rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.2);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(227, 6, 19, 0.3);
            color: white;
        }

        /* Select2 Customization */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 12px;
            min-height: 45px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        /* Badge Styling */
        .badge-soft-primary {
            background: rgba(227, 6, 19, 0.1);
            color: var(--primary);
            border-radius: 8px;
            padding: 0.5em 0.8em;
            font-weight: 600;
        }

        .forecast-table-wrap {
            border: 1px solid #1f2937;
            max-height: 68vh;
            overflow: auto;
            background: #fff;
        }

        .forecast-report-table {
            border-collapse: collapse;
            min-width: 1700px;
            width: max-content;
            margin: 0;
            font-size: 12px;
            line-height: 1.15;
        }

        .forecast-report-table th,
        .forecast-report-table td {
            border: 1px solid #1f2937;
            padding: 4px 6px;
            min-width: 64px;
            height: 20px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            color: #000;
        }

        .forecast-report-table thead th {
            font-weight: 700;
        }

        .forecast-report-table .fixed-head {
            background: #c9c9c9;
            min-width: 76px;
        }

        .forecast-report-table .fixed-head.light {
            background: #fff;
        }

        .forecast-report-table .part-name-col {
            min-width: 240px;
        }

        .forecast-report-table .row-label {
            background: #fff;
            font-weight: 700;
            min-width: 92px;
        }

        .forecast-report-table .period-title {
            background: #00b050;
            color: #000;
            font-weight: 700;
        }

        .forecast-report-table .forecast-title {
            background: #ffff00;
            font-weight: 700;
        }

        .forecast-report-table .period-date {
            background: #d9ead3;
        }

        .forecast-report-table .emergency-title {
            background: #00b050;
            min-width: 74px;
        }

        .forecast-report-table .emergency-fill {
            background: #ffff00;
        }

        .forecast-report-table .part-name-cell {
            text-align: left;
            white-space: nowrap;
        }

        .forecast-report-table .qty-cell {
            text-align: right;
        }

        .forecast-report-table .empty-cell {
            height: 96px;
            color: #64748b;
            font-size: 14px;
            background: #fff;
        }

        @media (max-width: 991.98px) {
            .main-wrapper { padding: 5rem 1rem 1.25rem; }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="content-container">
            <header class="dashboard-header d-flex justify-content-between align-items-end">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Outbound Report</li>
                        </ol>
                    </nav>
                    <h1>Outbound to Supplier</h1>
                    <p class="text-secondary mb-0">Report & Analytics for Supplier Outbound Transactions</p>
                </div>
                <div class="text-end d-none d-md-block">
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2" id="btnExport">
                        <i class="fas fa-file-export me-1"></i> Export Data
                    </button>
                    <span class="badge bg-white text-dark border p-2 rounded-3 shadow-sm">
                        <i class="fas fa-clock text-primary me-2"></i><?php echo date('d M Y, H:i'); ?>
                    </span>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="filter-card">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Plant</label>
                        <select class="form-select select2-filter" id="filterPlant" name="plant">
                            <option value="">All Plants</option>
                            <option value="1101">SAAB (1101)</option>
                            <option value="1100">SAB (1100)</option>
                            <option value="1800">SAM (1800)</option>
                            <option value="1400">SATC (1400)</option>
                            <option value="9001">SDC (9001)</option>
                            <option value="1200">SLAB (1200)</option>
                            <option value="1202">SLAB2 (1202)</option>
                            <option value="1203">SLAB3 (1203)</option>
                            <option value="1201">SRAB (1201)</option>
                            <option value="1300">SRDC (1300)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select select2-filter" id="filterSupplier" name="supplier">
                            <option value="">All Suppliers</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Part Number</label>
                        <input type="text" class="form-control" id="filterPart" name="part_no" placeholder="Search Part...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Report Date</label>
                        <input type="date" class="form-control" id="filterDate" name="report_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Reset</button>
                        <button type="submit" class="btn btn-filter rounded-pill px-5">
                            <i class="fas fa-search me-2"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Report Table Section -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Report Results</h5>
                    <div id="tableInfo" class="small text-muted">Showing results for selected filters</div>
                </div>
                
                <div class="forecast-table-wrap">
                    <table id="reportTable" class="forecast-report-table">
                        <thead id="reportHead"></thead>
                        <tbody id="reportBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            let currentPayload = null;

            const fixedHeaderColumns = [
                { label: 'SA', className: 'fixed-head' },
                { label: 'SA Item', className: 'fixed-head' },
                { label: 'PART NO.', className: 'fixed-head' },
                { label: 'PART NAME', className: 'fixed-head part-name-col' },
                { label: 'MODEL', className: 'fixed-head light' },
                { label: 'MPQ', className: 'fixed-head light' },
                { label: 'WOQ', className: 'fixed-head light' }
            ];

            const fixedBodyColumns = [
                'SA',
                'SA_ITEM',
                'PART_NO',
                'PART_NAME',
                'MODEL',
                'MPQ',
                'WOQ',
                'UOM',
                'EMERGENCY_AIR',
                'EMERGENCY_SEA'
            ];

            $('#filterPlant').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            $('#filterSupplier').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Supplier',
                width: '100%',
                ajax: {
                    url: 'api/get_suppliers.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            plant: $('#filterPlant').val()
                        };
                    },
                    processResults: function (data) {
                        const rows = Array.isArray(data) ? data : [];
                        return {
                            results: rows.map(s => ({ id: s.CODE, text: s.CODE + ' - ' + s.NAME }))
                        };
                    },
                    cache: true
                }
            });

            function escapeHtml(value) {
                const text = value === null || value === undefined ? '' : String(value);
                return $('<div>').text(text).html();
            }

            function formatQty(value) {
                const number = Number(value || 0);
                if (!isFinite(number)) {
                    return escapeHtml(value);
                }
                return number.toLocaleString('en-US', { maximumFractionDigits: 2 });
            }

            function formatYmd(value) {
                const text = value === null || value === undefined ? '' : String(value);
                if (/^\d{8}$/.test(text)) {
                    return `${text.slice(0, 4)}-${text.slice(4, 6)}-${text.slice(6, 8)}`;
                }
                return text;
            }

            function columnLabel(index) {
                let label = '';
                while (index > 0) {
                    const modulo = (index - 1) % 26;
                    label = String.fromCharCode(65 + modulo) + label;
                    index = Math.floor((index - modulo) / 26);
                }
                return label;
            }

            function getFilters() {
                return {
                    plant: $('#filterPlant').val(),
                    supplier: $('#filterSupplier').val(),
                    part_no: $('#filterPart').val(),
                    report_date: $('#filterDate').val(),
                    months: 6
                };
            }

            function renderHeader(periods) {
                const forecastCount = Math.max(periods.length - 1, 0);
                let html = '<tr>';

                fixedHeaderColumns.forEach(column => {
                    html += `<th rowspan="6" class="${column.className}">${escapeHtml(column.label)}</th>`;
                });

                html += '<th class="row-label"></th>';
                html += '<th class="emergency-title">Emergency Air</th>';
                html += '<th class="emergency-title">Emergency SEA</th>';

                if (periods.length > 0) {
                    html += `<th class="period-title">${escapeHtml(periods[0].title || 'Firm Order')}</th>`;
                    if (forecastCount > 0) {
                        html += `<th colspan="${forecastCount}" class="forecast-title">Forecast</th>`;
                    }
                }
                html += '</tr>';

                [
                    { label: 'Order release date', key: 'order_release_date' },
                    { label: 'Week order', key: 'week_order' },
                    { label: 'ETD', key: 'etd' },
                    { label: 'ETA', key: 'eta' },
                    { label: 'Delivery date', key: 'delivery_date' }
                ].forEach(row => {
                    html += `<tr><th class="row-label">${escapeHtml(row.label)}</th>`;
                    html += '<th class="emergency-fill"></th><th class="emergency-fill"></th>';
                    periods.forEach(period => {
                        html += `<th class="period-date">${escapeHtml(period[row.key])}</th>`;
                    });
                    html += '</tr>';
                });

                $('#reportHead').html(html);
            }

            function renderBody(rows, periods) {
                const colspan = fixedBodyColumns.length + periods.length;

                if (!rows.length) {
                    $('#reportBody').html(`<tr><td colspan="${colspan}" class="empty-cell">No data found for selected filters</td></tr>`);
                    return;
                }

                let html = '';
                rows.forEach((row, rowIndex) => {
                    html += '<tr>';
                    fixedBodyColumns.forEach(key => {
                        const className = key === 'PART_NAME' ? 'part-name-cell' : '';
                        html += `<td class="${className}">${escapeHtml(row[key])}</td>`;
                    });

                    periods.forEach((period, periodIndex) => {
                        const qty = row.quantities && row.quantities[period.key] !== undefined ? row.quantities[period.key] : 0;
                        html += `<td class="qty-cell">${formatQty(qty)}</td>`;
                    });

                    html += '</tr>';
                });

                $('#reportBody').html(html);
            }

            function updateInfo(rows, periods) {
                const totalColumns = fixedBodyColumns.length + periods.length;
                const periodRange = periods.length ? `K-${columnLabel(totalColumns)}` : '-';
                $('#tableInfo').text(`Rows: ${rows.length.toLocaleString('en-US')} | Period columns: ${periodRange}`);
            }

            function renderReport(payload) {
                const periods = payload.periods || [];
                const rows = payload.data || [];
                currentPayload = payload;

                renderHeader(periods);
                renderBody(rows, periods);
                updateInfo(rows, periods);
            }

            function showLoading() {
                $('#reportHead').empty();
                $('#reportBody').html('<tr><td class="empty-cell">Loading report...</td></tr>');
                $('#tableInfo').text('Loading...');
            }

            function loadReport() {
                showLoading();
                $.getJSON('api/get_supplier_report.php', getFilters())
                    .done(function(res) {
                        if (!res || res.status !== 'success') {
                            const message = res && res.message ? res.message : 'Unable to load report data.';
                            renderReport({ periods: [], data: [] });
                            Swal.fire('Report Error', message, 'error');
                            return;
                        }
                        renderReport(res);
                    })
                    .fail(function(xhr) {
                        renderReport({ periods: [], data: [] });
                        Swal.fire('Report Error', xhr.responseText || 'Unable to connect to report API.', 'error');
                    });
            }

            function exportCurrentTable() {
                if (!currentPayload) {
                    Swal.fire('Export Data', 'Please generate the report before exporting.', 'info');
                    return;
                }

                const tableHtml = document.getElementById('reportTable').outerHTML;
                const exportHtml = `
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
                            th, td { border: 1px solid #000; padding: 4px 6px; text-align: center; white-space: nowrap; }
                            .fixed-head { background: #c9c9c9; font-weight: bold; }
                            .fixed-head.light, .row-label { background: #fff; font-weight: bold; }
                            .period-title, .emergency-title { background: #00b050; font-weight: bold; }
                            .forecast-title, .emergency-fill { background: #ffff00; font-weight: bold; }
                            .period-date { background: #d9ead3; }
                            .part-name-cell { text-align: left; }
                            .qty-cell { text-align: right; }
                        </style>
                    </head>
                    <body>${tableHtml}</body>
                    </html>`;

                const blob = new Blob(['\ufeff', exportHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const link = document.createElement('a');
                const fileDate = $('#filterDate').val() || new Date().toISOString().slice(0, 10);
                link.href = URL.createObjectURL(blob);
                link.download = `supplier_outbound_${fileDate}.xls`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            }

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                loadReport();
            });

            $('#filterForm').on('reset', function() {
                setTimeout(() => {
                    $('#filterPlant').trigger('change');
                    $('#filterSupplier').val(null).trigger('change');
                    
                    // Clear the table results on reset instead of loading with defaults
                    $('#reportHead').empty();
                    $('#reportBody').empty();
                    $('#tableInfo').text('Filters reset. Please select filters and click Generate.');
                    currentPayload = null;
                }, 10);
            });

            $('#btnExport').on('click', exportCurrentTable);

            // Initial state: empty table with prompt
            $('#reportBody').html('<tr><td class="empty-cell">Please select filters and click Generate Report to view data.</td></tr>');
        });
    </script>
</body>
</html>
