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
    <title>Forecast Analytics - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        .main-wrapper { transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .analytics-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .timeline-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .timeline-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 10px;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .timeline-table td {
            padding: 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
            font-size: 0.9rem;
        }
        .section-header {
            background: #1e293b;
            color: white;
            padding: 10px 15px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .qty-cell {
            font-weight: 700;
            color: #e30613;
        }
        
        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 12px;
            padding: 0.5rem;
            height: auto;
            border-color: #e2e8f0;
        }

        .table-responsive::-webkit-scrollbar { height: 8px; }
        .table-responsive::-webkit-scrollbar-track { background: #f1f5f9; }
        .table-responsive::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .forecast-grid {
            background: #f8fafc;
            border-radius: 12px;
        }
        .forecast-unit {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            min-width: 100px;
            text-align: center;
            overflow: hidden;
            flex-grow: 0;
        }
        .unit-date {
            background: #f1f5f9;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        .unit-qty {
            padding: 8px 5px;
            font-weight: 700;
            color: #e30613;
            font-size: 0.95rem;
        }
        .part-analysis-block {
            border-left: 6px solid #e30613;
        }
    </style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<div class="main-wrapper">
    <div class="content-container">
        <header class="dashboard-header mb-4">
            <h1 class="fw-bold">Forecast Analytics</h1>
            <p class="text-secondary mb-0">Part-wise demand visibility and timeline analysis</p>
        </header>

        <!-- Filter Panel -->
        <div class="analytics-card">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">PLANT</label>
                    <select class="form-select form-select-lg rounded-4 border-0 bg-light" id="plantSelect">
                        <option value="1101">SAAB (1101)</option>
                        <option value="1100">SAB (1100)</option>
                        <option value="1800" selected>SAM (1800)</option>
                        <option value="1400">SATC (1400)</option>
                        <option value="9001">SDC (9001)</option>
                        <option value="1200">SLAB (1200)</option>
                        <option value="1202">SLAB2 (1202)</option>
                        <option value="1203">SLAB3 (1203)</option>
                        <option value="1201">SRAB (1201)</option>
                        <option value="1300">SRDC (1300)</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-secondary">PART NUMBER SEARCH (Optional)</label>
                    <select class="form-select" id="partSelect" data-placeholder="Type Part Number or Name...">
                        <option></option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-dark w-100 py-3 rounded-4 shadow-sm fw-bold" id="btnSearch">
                        <i class="fas fa-search me-2"></i> EXPLORE
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Panel -->
        <div id="resultContent" class="d-none">
            <!-- Dynamically populated -->
        </div>

        <div id="welcomeMessage" class="text-center py-5">
            <div class="opacity-25 mb-4">
                <i class="fas fa-microchip fa-6x text-secondary"></i>
            </div>
            <h3 class="fw-bold text-secondary">Awaiting Parameters</h3>
            <p class="text-muted mx-auto" style="max-width: 400px;">Please specify the target plant or select a specific part number to visualize the intelligence pipeline.</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 with remote data
    $('#partSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        ajax: {
            url: 'api/get_part_list.php',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term,
                    plant: $('#plantSelect').val()
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(item => ({
                        id: item.PART_NO,
                        text: item.PART_NO + ' : ' + item.PART_NAME
                    }))
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    $('#btnSearch').click(function() {
        const plant = $('#plantSelect').val();
        const part_no = $('#partSelect').val();
        const btn = $(this);

        if(!plant) {
            return Swal.fire('Error', 'Please select a plant.', 'error');
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>EXPLORING...');
        $('#welcomeMessage').addClass('d-none');
        
        $.get('api/get_part_forecast_detail.php', { plant: plant, part_no: part_no }, function(res) {
            if(res.status === 'success') {
                const resultsContainer = $('#resultContent').empty().removeClass('d-none').show();
                
                if (res.data.length === 0) {
                    resultsContainer.html('<div class="analytics-card text-center"><i class="fas fa-info-circle fa-3x text-muted mb-3"></i><p>No records found for this criteria.</p></div>');
                    return;
                }

                res.data.forEach((p, index) => {
                    const partSection = $(`
                        <div class="part-analysis-block mb-5 p-4 bg-white rounded-4 shadow-sm border anim-fade-up">
                            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3">
                                    <i class="fas fa-cube fa-2x"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 text-dark">${p.part_info.PART_NO}</h3>
                                    <p class="text-secondary mb-0">${p.part_info.PART_NAME}</p>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-12">
                                    <h6 class="fw-bold mb-3 text-success"><i class="fas fa-calendar-day me-2"></i>Day Forecast</h6>
                                    <div id="day_container_${index}"></div>
                                </div>
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-calendar-week me-2"></i>Week Forecast</h6>
                                    <div id="week_container_${index}"></div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    resultsContainer.append(partSection);
                    renderTable(`day_container_${index}`, p.daily);
                    renderTable(`week_container_${index}`, p.weekly);
                });

                $('html, body').animate({ scrollTop: resultsContainer.offset().top - 50 }, 500);
            } else {
                Swal.fire('Error', res.message, 'error');
                $('#welcomeMessage').removeClass('d-none');
                $('#resultContent').addClass('d-none');
            }
        }).fail(() => {
            Swal.fire('Error', 'Server communication failed.', 'error');
        }).always(() => {
            btn.prop('disabled', false).html('<i class="fas fa-search me-2"></i> EXPLORE');
        });
    });

    function renderTable(containerId, records) {
        const container = $('#' + containerId).empty();
        
        if(!records || records.length === 0) {
            container.append('<div class="text-center py-3 text-muted border rounded-3 bg-light small">NO DATA</div>');
            return;
        }

        const grid = $('<div class="forecast-grid d-flex flex-wrap gap-2 p-2"></div>');
        
        records.forEach(r => {
            const unit = $(`
                <div class="forecast-unit shadow-sm">
                    <div class="unit-date">${r.F_DATE}</div>
                    <div class="unit-qty">${parseFloat(r.F_QTY).toLocaleString()}</div>
                </div>
            `);
            grid.append(unit);
        });

        container.append(grid);
    }
});
</script>
</body>
</html>
