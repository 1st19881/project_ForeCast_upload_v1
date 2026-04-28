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
    <title>Forecast List - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        /* Main Content Styling */
        .main-wrapper {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            max-width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-field-plant { width: 200px; }
        .filter-field-size { width: 120px; }

        .content-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .part-badge {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: inherit;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .badge-day { background: #ecfdf5; color: #059669; }
        .badge-week { background: #eff6ff; color: #2563eb; }

        .pagination-container {
            padding: 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background: #b3050f;
            color: white;
            transform: translateY(-1px);
        }

        .empty-state {
            padding: 100px 0;
            text-align: center;
            color: #94a3b8;
        }
        .empty-state i { font-size: 4rem; opacity: 0.5; margin-bottom: 1rem; }

        @media (max-width: 991.98px) {
            .main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 5rem 1rem 1.25rem;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 1rem;
            }

            .page-header > .d-flex {
                width: 100%;
                flex-wrap: wrap;
                gap: .5rem !important;
            }

            .filter-card {
                align-items: stretch;
                flex-direction: column;
                padding: 1rem;
            }

            .filter-field-plant,
            .filter-field-size {
                width: 100%;
            }

            .btn-action {
                width: 100%;
            }

            .pagination-container {
                align-items: flex-start !important;
                flex-direction: column;
                gap: .75rem;
            }
        }

        @media (max-width: 575.98px) {
            .main-wrapper {
                padding-left: .85rem;
                padding-right: .85rem;
            }

            .table thead th,
            .table tbody td {
                padding: .85rem 1rem;
                white-space: nowrap;
            }

            .empty-state {
                padding: 64px 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<?php include 'components/sidebar.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-wrapper">
    <div class="page-header">
        <div>
            <h2 class="fw-bold mb-1">Forecast Analysis</h2>
            <p class="text-muted mb-0">Monitor and filter manufacturing forecast reports.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-white shadow-sm text-dark px-3 py-2 rounded-pill fw-bold border" id="totalCountBadge" style="display:none;">
                <i class="fas fa-database text-danger me-2"></i>Total Records: <span id="totalCountVal">0</span>
            </span>
            <span class="badge bg-white shadow-sm text-dark px-3 py-2 rounded-pill fw-bold border" id="totalPagesBadge" style="display:none;">
                <i class="fas fa-file-alt text-primary me-2"></i>Total Pages: <span id="totalPagesVal">0</span>
            </span>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-4" id="btnClearData">
                <i class="fas fa-trash-alt me-1"></i> Clear Plant Data
            </button>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="filter-card">
        <div class="filter-field-plant">
            <label class="small fw-bold text-secondary mb-1">Manufacturing Plant</label>
            <select class="form-select" id="filterPlant">
                <option value="">All Plants</option>
                <option value="1101">SAAB</option>
                <option value="1100">SAB</option>
                <option value="1800">SAM</option>
                <option value="1400">SATC</option>
                <option value="9001">SDC</option>
                <option value="1200">SLAB</option>
                <option value="1202">SLAB2</option>
                <option value="1203">SLAB3</option>
                <option value="1201">SRAB</option>
                <option value="1300">SRDC</option>
            </select>
        </div>
        <div class="flex-grow-1">
            <label class="small fw-bold text-secondary mb-1">Search Keywords</label>
            <input type="text" class="form-control" id="searchInput" placeholder="Search by Part Number or Name...">
        </div>
        <div class="filter-field-size">
            <label class="small fw-bold text-secondary mb-1">Page Size</label>
            <select class="form-select" id="pageSize">
                <option value="10">10 Rows</option>
                <option value="20" selected>20 Rows</option>
                <option value="50">50 Rows</option>
                <option value="100">100 Rows</option>
            </select>
        </div>
        <button class="btn btn-action" id="btnSearch">
            <i class="fas fa-search me-1"></i> Filter
        </button>
    </div>

    <!-- Data Container -->
    <div class="content-card">
        <div id="dataContent">
            <div class="empty-state">
                <i class="fas fa-database"></i>
                <h3>Ready to Sync</h3>
                <p>Select a plant or enter a search term above to display results.</p>
            </div>
        </div>
        
        <div class="pagination-container d-flex justify-content-between align-items-center" id="paginationArea" style="display: none !important;">
            <div class="small fw-bold text-secondary" id="paginationInfo"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentPage = 1;

    $('#pageSize').change(function() {
        currentPage = 1;
        loadData();
    });

    function loadData() {
        const plant = $('#filterPlant').val();
        const search = $('#searchInput').val();
        const limit = $('#pageSize').val();
        
        if (!plant && !search) {
             Swal.fire({ icon: 'info', text: 'Select a Plant or search to fetch data', timer: 1500, showConfirmButton: false });
             return;
        }

            $('#dataContent').html(`
                <div class="text-center py-5 anim-fade-up">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h5 class="text-secondary fw-bold">Analyzing Intelligence...</h5>
                    <p class="text-muted small">Fetching latest forecast records from Oracle</p>
                </div>
            `);

        $.get('api/get_forecasts.php', { plant: plant, search: search, page: currentPage, limit: limit }, function(res) {
            if (res.status === 'success') {
                $('#totalCountBadge').fadeIn();
                $('#totalPagesBadge').fadeIn();
                $('#totalCountVal').text(res.pagination.total.toLocaleString());
                $('#totalPagesVal').text(res.pagination.total_pages.toLocaleString());
                renderTable(res.data);
                renderPagination(res.pagination);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    }

    function renderTable(data) {
        if (data.length === 0) {
            $('#dataContent').html('<div class="empty-state"><i class="fas fa-search"></i><h3>No results</h3><p>We couldn\'t find any records matching your filter.</p></div>');
            $('#paginationArea').attr('style', 'display: none !important');
            return;
        }

        let html = '<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Plant</th><th>Part Information</th><th>Type</th><th>Target Date</th><th class="text-end">Qty</th><th class="text-end">Created</th></tr></thead><tbody>';
        data.forEach(row => {
            const label = row.FORECAST_TYPE === 'D' ? 'DAY' : (row.FORECAST_TYPE === 'W' ? 'WEEK' : row.FORECAST_TYPE);
            const badge = (row.FORECAST_TYPE === 'D' || row.FORECAST_TYPE === 'DAY') ? 'badge-day' : 'badge-week';
            
            html += `
                <tr>
                    <td class="fw-bold">${row.PLANT}</td>
                    <td>
                        <div class="part-badge mb-1" style="width: fit-content;">${row.PART_NO}</div>
                        <div class="small text-muted text-truncate" style="max-width: 280px;">${row.PART_NAME}</div>
                    </td>
                    <td><span class="badge-status ${badge}">${label}</span></td>
                    <td class="fw-bold text-dark">${row.FORECAST_DATE_DISPLAY}</td>
                    <td class="text-end fw-bold text-danger">${parseFloat(row.FORECAST_QTY).toLocaleString()}</td>
                    <td class="text-end small text-muted">${row.CREATED_AT}</td>
                </tr>
            `;
        });
        html += '</tbody></table></div>';
        $('#dataContent').html(html);
        $('#paginationArea').attr('style', 'display: flex !important');
    }

    function renderPagination(pg) {
        $('#paginationInfo').text(`Showing Page ${pg.page} of ${pg.total_pages} (${pg.total} Total)`);
        let html = '';
        html += `<li class="page-item ${pg.page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pg.page - 1}">&lsaquo;</a></li>`;
        
        let start = Math.max(1, pg.page - 2);
        let end = Math.min(pg.total_pages, start + 4);
        if (end - start < 4) start = Math.max(1, end - 4);

        for (let i = start; i <= end; i++) {
            if (i < 1) continue;
            html += `<li class="page-item ${i === pg.page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        html += `<li class="page-item ${pg.page === pg.total_pages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pg.page + 1}">&rsaquo;</a></li>`;
        $('#paginationList').html(html);

        $('.page-link').on('click', function(e) {
            e.preventDefault();
            const p = $(this).data('page');
            if (p && p !== pg.page) {
                currentPage = p;
                loadData();
            }
        });
    }

    $('#btnSearch').click(function() { currentPage = 1; loadData(); });
    $('#filterPlant').change(function() { currentPage = 1; loadData(); });
    $('#searchInput').keypress(function(e) { if(e.which===13){ currentPage=1; loadData(); } });

    $('#btnClearData').click(function() {
        const plant = $('#filterPlant').val();
        if(!plant) return Swal.fire('Warning', 'Select a Plant to clear.', 'warning');
        Swal.fire({ title: 'Delete Plant Data?', text: `Wipe all records for ${plant}?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, Delete' })
        .then(res => {
            if(res.isConfirmed) $.post('api/clear_data.php', { plant: plant }, () => { Swal.fire('Deleted','Data wiped','success'); loadData(); });
        });
    });
</script>

</body>
</html>
