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
    <title>Supplier Report - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root { --glass-bg: rgba(255,255,255,0.9); --primary: #e30613; --primary-dark: #b3050f; --secondary: #64748b; }
        body { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); font-family: 'Inter', sans-serif; color: #1e293b; }
        .content-container { max-width: 1600px; margin: 0 auto; }
        .dashboard-header { margin-bottom: 2rem; }
        .dashboard-header h1 { font-size: 2.2rem; font-weight: 800; background: linear-gradient(90deg, #1e293b 0%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.25rem; }
        .filter-card { background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px; border: 1px solid rgba(255,255,255,0.8); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 1.5rem; margin-bottom: 2rem; }
        .form-label { font-weight: 600; font-size: 0.85rem; color: var(--secondary); margin-bottom: 0.5rem; }
        .form-control, .form-select { border-radius: 12px; padding: 0.65rem 1rem; border: 1px solid #e2e8f0; background-color: #f8fafc; transition: all 0.2s; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 4px rgba(227,6,19,0.1); border-color: var(--primary); }
        .report-card { background: white; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 15px rgba(0,0,0,0.02); padding: 1.5rem; overflow: hidden; }
        .btn-filter { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border: none; border-radius: 12px; padding: 0.65rem 1.5rem; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 12px rgba(227,6,19,0.2); }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(227,6,19,0.3); color: white; }
        .select2-container--bootstrap-5 .select2-selection { border-radius: 12px; min-height: 45px; border: 1px solid #e2e8f0; background-color: #f8fafc; }

        /* Report Table */
        .forecast-table-wrap { border: 1px solid #1f2937; max-height: 68vh; overflow: auto; background: #fff; }
        .forecast-report-table { border-collapse: collapse; min-width: 1700px; width: max-content; margin: 0; font-size: 12px; line-height: 1.15; }
        .forecast-report-table th, .forecast-report-table td { border: 1px solid #1f2937; padding: 4px 6px; min-width: 64px; height: 20px; text-align: center; vertical-align: middle; white-space: nowrap; color: #000; }
        .forecast-report-table thead th { font-weight: 700; }
        .forecast-report-table .fixed-head { background: #c9c9c9; min-width: 76px; }
        .forecast-report-table .fixed-head.light { background: #fff; }
        .forecast-report-table .part-name-col { min-width: 240px; }
        .forecast-report-table .row-label { background: #fff; font-weight: 700; min-width: 92px; }
        .forecast-report-table .period-title { background: #00b050; color: #000; font-weight: 700; }
        .forecast-report-table .forecast-title { background: #ffff00; font-weight: 700; }
        .forecast-report-table .period-date { background: #d9ead3; }
        .forecast-report-table .emergency-title { background: #00b050; min-width: 74px; }
        .forecast-report-table .emergency-fill { background: #ffff00; }
        .forecast-report-table .part-name-cell { text-align: left; white-space: nowrap; }
        .forecast-report-table .qty-cell { text-align: right; cursor: pointer; }
        .forecast-report-table .qty-cell:hover { background: #e8f5e9; font-weight: 700; }
        .forecast-report-table .empty-cell { height: 96px; color: #64748b; font-size: 14px; background: #fff; }

        /* Drilldown Modal */
        .drilldown-table { font-size: 12px; }
        .drilldown-table th { background: #f1f5f9; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; }
        .drilldown-table td { vertical-align: middle; }
        .modal-header-gradient { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border: none; }

        @media (max-width: 991.98px) { .main-wrapper { padding: 5rem 1rem 1.25rem; } }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="content-container">
            <header class="dashboard-header d-flex justify-content-between align-items-end">
                <div>
                    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Supplier Report</li>
                    </ol></nav>
                    <h1>Supplier Report</h1>
                    <p class="text-secondary mb-0">Forecast Consumption Report by Supplier</p>
                </div>
                <div class="text-end d-none d-md-block">
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2" id="btnExport"><i class="fas fa-file-export me-1"></i> Export</button>
                    <span class="badge bg-white text-dark border p-2 rounded-3 shadow-sm"><i class="fas fa-clock text-primary me-2"></i><?php echo date('d M Y, H:i'); ?></span>
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
                        <select class="form-select select2-filter" id="filterSupplier" name="supplier"><option value="">All Suppliers</option></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Part Number</label>
                        <input type="text" class="form-control" id="filterPart" name="part_no" placeholder="Search Part...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Report Date</label>
                        <input type="date" class="form-control" id="filterDate" name="report_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Months</label>
                        <select class="form-select" id="filterMonths" name="months">
                            <option value="3">3 Months</option>
                            <option value="6" selected>6 Months</option>
                            <option value="9">9 Months</option>
                            <option value="12">12 Months</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Reset</button>
                        <button type="submit" class="btn btn-filter rounded-pill px-5"><i class="fas fa-search me-2"></i> Generate Report</button>
                    </div>
                </form>
            </div>

            <!-- Report Table -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Report Results</h5>
                    <div id="tableInfo" class="small text-muted">Select filters and click Generate Report</div>
                </div>
                <div class="forecast-table-wrap">
                    <table id="reportTable" class="forecast-report-table">
                        <thead id="reportHead"></thead>
                        <tbody id="reportBody">
                            <tr><td class="empty-cell">Please select filters and click Generate Report to view data.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Drilldown Modal -->
    <div class="modal fade" id="drilldownModal" tabindex="-1" aria-labelledby="drilldownTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header modal-header-gradient">
                    <h5 class="modal-title" id="drilldownTitle"><i class="fas fa-layer-group me-2"></i>Consumption Drilldown</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 bg-light border-bottom" id="drilldownSummary"></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover drilldown-table mb-0">
                            <thead><tr>
                                <th>Plant</th><th>FG Part No</th><th>FG Part Name</th>
                                <th>Type</th><th>Forecast Date</th><th>First Day of Week</th>
                                <th>Supplier</th><th>Part No</th><th>Part Name</th>
                                <th class="text-end">Forecast Qty</th><th class="text-end">Usage Qty</th><th class="text-end">Consump Qty</th>
                            </tr></thead>
                            <tbody id="drilldownBody"><tr><td colspan="12" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

        const fixedBodyColumns = ['SA','SA_ITEM','PART_NO','PART_NAME','MODEL','MPQ','WOQ','UOM','EMERGENCY_AIR','EMERGENCY_SEA'];

        $('#filterPlant').select2({ theme: 'bootstrap-5', width: '100%' });
        $('#filterSupplier').select2({
            theme: 'bootstrap-5', placeholder: 'Select Supplier', width: '100%',
            ajax: {
                url: 'api/get_suppliers.php', dataType: 'json', delay: 250,
                data: function(params) { return { q: params.term, plant: $('#filterPlant').val() }; },
                processResults: function(data) {
                    const rows = Array.isArray(data) ? data : [];
                    return { results: rows.map(s => ({ id: s.CODE, text: s.CODE + ' - ' + s.NAME })) };
                },
                cache: true
            }
        });

        function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
        function fmtQty(v) { const n = Number(v||0); return isFinite(n) ? n.toLocaleString('en-US',{maximumFractionDigits:2}) : esc(v); }
        function colLabel(i) { let s=''; while(i>0){const m=(i-1)%26; s=String.fromCharCode(65+m)+s; i=Math.floor((i-m)/26);} return s; }

        function getFilters() {
            return { plant: $('#filterPlant').val(), supplier: $('#filterSupplier').val(), part_no: $('#filterPart').val(), report_date: $('#filterDate').val(), months: $('#filterMonths').val() };
        }

        function renderHeader(periods) {
            const fc = Math.max(periods.length - 1, 0);
            let h = '<tr>';
            fixedHeaderColumns.forEach(c => { h += `<th rowspan="6" class="${c.className}">${esc(c.label)}</th>`; });
            h += '<th class="row-label"></th><th class="emergency-title">Emergency Air</th><th class="emergency-title">Emergency SEA</th>';
            if (periods.length > 0) {
                h += `<th class="period-title">${esc(periods[0].title||'Firm Order')}</th>`;
                if (fc > 0) h += `<th colspan="${fc}" class="forecast-title">Forecast</th>`;
            }
            h += '</tr>';

            [{label:'Order release date',key:'order_release_date'},{label:'Week order',key:'week_order'},{label:'ETD',key:'etd'},{label:'ETA',key:'eta'},{label:'Delivery date',key:'delivery_date'}].forEach(row => {
                h += `<tr><th class="row-label">${esc(row.label)}</th><th class="emergency-fill"></th><th class="emergency-fill"></th>`;
                periods.forEach(p => { h += `<th class="period-date">${esc(p[row.key])}</th>`; });
                h += '</tr>';
            });
            $('#reportHead').html(h);
        }

        function renderBody(rows, periods) {
            const cs = fixedBodyColumns.length + periods.length;
            if (!rows.length) { $('#reportBody').html(`<tr><td colspan="${cs}" class="empty-cell">No data found for selected filters</td></tr>`); return; }
            let h = '';
            rows.forEach((row, ri) => {
                h += '<tr>';
                fixedBodyColumns.forEach(k => {
                    const cls = k === 'PART_NAME' ? 'part-name-cell' : '';
                    h += `<td class="${cls}">${esc(row[k])}</td>`;
                });
                periods.forEach((p, pi) => {
                    const qty = row.quantities && row.quantities[p.key] !== undefined ? row.quantities[p.key] : 0;
                    h += `<td class="qty-cell" data-row="${ri}" data-period="${pi}" title="Click for drilldown">${fmtQty(qty)}</td>`;
                });
                h += '</tr>';
            });
            $('#reportBody').html(h);
        }

        function updateInfo(rows, periods) {
            const tc = fixedBodyColumns.length + periods.length;
            const pr = periods.length ? `K-${colLabel(tc)}` : '-';
            $('#tableInfo').text(`Rows: ${rows.length.toLocaleString('en-US')} | Period columns: ${pr}`);
        }

        function renderReport(payload) {
            const periods = payload.periods || [], rows = payload.data || [];
            currentPayload = payload;
            renderHeader(periods);
            renderBody(rows, periods);
            updateInfo(rows, periods);
        }

        function showLoading() {
            $('#reportHead').empty();
            $('#reportBody').html('<tr><td class="empty-cell"><i class="fas fa-spinner fa-spin me-2"></i>Loading report...</td></tr>');
            $('#tableInfo').text('Loading...');
        }

        function loadReport() {
            showLoading();
            $.getJSON('api/get_supplier_report.php', getFilters())
                .done(function(res) {
                    if (!res || res.status !== 'success') {
                        renderReport({ periods: [], data: [] });
                        Swal.fire('Report Error', res && res.message ? res.message : 'Unable to load report.', 'error');
                        return;
                    }
                    renderReport(res);
                })
                .fail(function(xhr) {
                    renderReport({ periods: [], data: [] });
                    Swal.fire('Report Error', xhr.responseText || 'Unable to connect to report API.', 'error');
                });
        }

        // Drilldown click handler
        $(document).on('click', '.qty-cell', function() {
            const ri = $(this).data('row'), pi = $(this).data('period');
            if (!currentPayload) return;
            const row = currentPayload.data[ri], period = currentPayload.periods[pi];
            if (!row || !period) return;

            const params = {
                plant: row.PLANT, supplier: row.SUPPLIER,
                part_no: row.PART_NO_RAW, fg_part: row.FG_PART_RAW,
                first_day_of_week: period.first_day_of_week
            };

            $('#drilldownTitle').html(`<i class="fas fa-layer-group me-2"></i>Drilldown: ${esc(row.PART_NO)} — ${esc(period.order_release_date)} (${esc(period.week_order)})`);
            $('#drilldownSummary').html(`<span class="badge bg-primary me-2">${esc(row.PLANT)}</span><strong>${esc(row.PART_NO)}</strong> — ${esc(row.PART_NAME)} <span class="ms-3 text-muted">Period: ${esc(period.order_release_date)}</span>`);
            $('#drilldownBody').html('<tr><td colspan="12" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</td></tr>');
            new bootstrap.Modal('#drilldownModal').show();

            $.getJSON('api/get_supplier_report_drilldown.php', params)
                .done(function(res) {
                    if (!res || res.status !== 'success' || !res.data.length) {
                        $('#drilldownBody').html('<tr><td colspan="12" class="text-center text-muted py-4">No drilldown data found</td></tr>');
                        return;
                    }
                    let h = '';
                    res.data.forEach(d => {
                        h += `<tr>
                            <td>${esc(d.PLANT)}</td><td>${esc(d.FG_PART_NO)}</td><td>${esc(d.FG_PART_NAME)}</td>
                            <td><span class="badge ${d.FORECAST_TYPE==='D'?'bg-info':'bg-warning text-dark'}">${esc(d.FORECAST_TYPE)}</span></td>
                            <td>${esc(d.FORECAST_DATE)}</td><td>${esc(d.FIRST_DAY_OF_WEEK)}</td>
                            <td>${esc(d.SUPPLIER)}</td><td>${esc(d.PART_NO)}</td><td>${esc(d.PART_NAME)}</td>
                            <td class="text-end">${fmtQty(d.FORECAST_QTY)}</td><td class="text-end">${fmtQty(d.USAGE_QTY)}</td>
                            <td class="text-end fw-bold">${fmtQty(d.CONSUMP_QTY)}</td>
                        </tr>`;
                    });
                    // Summary row
                    h += `<tr class="table-dark fw-bold">
                        <td colspan="9" class="text-end">Total</td>
                        <td class="text-end">${fmtQty(res.summary.total_forecast_qty)}</td><td></td>
                        <td class="text-end">${fmtQty(res.summary.total_consump_qty)}</td>
                    </tr>`;
                    $('#drilldownBody').html(h);
                })
                .fail(function() { $('#drilldownBody').html('<tr><td colspan="12" class="text-center text-danger py-4">Failed to load drilldown data</td></tr>'); });
        });

        // Export
        function exportTable() {
            if (!currentPayload) { Swal.fire('Export','Please generate the report first.','info'); return; }
            const html = `<html><head><meta charset="UTF-8"><style>
                table{border-collapse:collapse;font-family:Arial;font-size:11px}
                th,td{border:1px solid #000;padding:4px 6px;text-align:center;white-space:nowrap}
                .fixed-head{background:#c9c9c9;font-weight:bold}.fixed-head.light,.row-label{background:#fff;font-weight:bold}
                .period-title,.emergency-title{background:#00b050;font-weight:bold}.forecast-title,.emergency-fill{background:#ffff00;font-weight:bold}
                .period-date{background:#d9ead3}.part-name-cell{text-align:left}.qty-cell{text-align:right}
            </style></head><body>${document.getElementById('reportTable').outerHTML}</body></html>`;
            const blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `supplier_report_${$('#filterDate').val()||new Date().toISOString().slice(0,10)}.xls`;
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(a.href);
        }

        $('#filterForm').on('submit', function(e) { e.preventDefault(); loadReport(); });
        $('#filterForm').on('reset', function() {
            setTimeout(() => {
                $('#filterPlant').trigger('change');
                $('#filterSupplier').val(null).trigger('change');
                $('#reportHead').empty(); $('#reportBody').html('<tr><td class="empty-cell">Please select filters and click Generate Report.</td></tr>');
                $('#tableInfo').text('Filters reset.'); currentPayload = null;
            }, 10);
        });
        $('#btnExport').on('click', exportTable);
    });
    </script>
</body>
</html>
