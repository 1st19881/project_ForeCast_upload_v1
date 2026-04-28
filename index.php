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
    <title>Forecast Upload - SAIC Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.9);
            --sidebar-width: 260px;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .main-wrapper {
            padding: 2.5rem;
            min-height: 100vh;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Modern Dashboard Header */
        .dashboard-header {
            margin-bottom: 3rem;
            position: relative;
        }
        .dashboard-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, #1e293b 0%, #e30613 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        /* Layout Grid */
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2.5rem;
            align-items: start;
        }

        /* Premium Upload Card */
        .premium-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            padding: 3.5rem;
            transition: transform 0.3s ease;
        }

        .drop-zone {
            height: 350px;
            border: 2px dashed #cbd5e1;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(248, 250, 252, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            margin: 2.5rem 0;
            position: relative;
            overflow: hidden;
        }

        .drop-zone:hover, .drop-zone.drag-over {
            border-color: var(--primary);
            background: rgba(227, 6, 19, 0.02);
            box-shadow: inset 0 0 40px rgba(227, 6, 19, 0.03);
        }

        .drop-zone i {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 10px 15px rgba(227, 6, 19, 0.2));
            transition: 0.3s;
        }

        .drop-zone:hover i { transform: translateY(-10px); }

        .btn-sync {
            width: 100%;
            padding: 1.25rem;
            border-radius: 18px;
            background: linear-gradient(135deg, #e30613 0%, #b3050f 100%);
            color: white;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            box-shadow: 0 15px 30px rgba(227, 6, 19, 0.25);
            transition: all 0.3s;
        }

        .btn-sync:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(227, 6, 19, 0.35);
        }

        /* Sidebar Info Cards */
        .info-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tip-card {
            background: white;
            padding: 1.5rem;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .status-badge {
            width: 10px; height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .select2-upload + .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 58px;
            border: 0;
            border-radius: 1rem;
            background-color: #f8f9fa;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            display: flex;
            align-items: center;
            padding: .7rem 1rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #111827;
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.4;
            padding-left: 0;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            right: 1rem;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection {
            box-shadow: 0 0 0 .25rem rgba(227, 6, 19, .12), 0 .125rem .25rem rgba(0,0,0,.075);
        }

        .select2-container--bootstrap-5.select2-container--disabled .select2-selection {
            opacity: .72;
            background-color: #f8f9fa;
        }

        .customer-select-wrap .select2-container--bootstrap-5 .select2-selection {
            padding-right: 3rem;
        }

        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .12);
            overflow: hidden;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            border-color: #e2e8f0;
            border-radius: .75rem;
            padding: .65rem .85rem;
        }

        #fileInput { display: none; }

        @media (max-width: 1200px) {
            .upload-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 991.98px) {
            .main-wrapper {
                padding: 5rem 1rem 1.25rem;
            }

            .dashboard-header {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }

            .premium-card {
                padding: 1.25rem;
                border-radius: 20px;
            }

            .premium-card .card-body {
                padding: 1rem !important;
            }

            .drop-zone {
                height: 260px;
                margin: 1.5rem 0;
            }

            .drop-zone i {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 575.98px) {
            .main-wrapper {
                padding-left: .85rem;
                padding-right: .85rem;
            }

            .dashboard-header h1 {
                font-size: 1.75rem;
            }

            .drop-zone {
                height: 220px;
            }

            .select2-container--bootstrap-5 .select2-selection {
                min-height: 52px;
            }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="content-container">
            <header class="dashboard-header d-flex justify-content-between align-items-end">
                <div>
                    <h1 class="fw-bold">Intelligence Upload</h1>
                    <p class="text-secondary mb-0">SAIC Motor Forecast & Inventory Synchronization Centre</p>
                </div>
                <div class="text-end d-none d-md-block">
                    <span class="badge bg-white text-dark border p-2 rounded-3 shadow-sm">
                        <i class="fas fa-clock text-primary me-2"></i>Last Updated: <?php echo date('d M Y, H:i'); ?>
                    </span>
                </div>
            </header>

            <div class="upload-grid">
                <!-- Left Side: Main Upload Card -->
                <div class="premium-card">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-4">
                            <h3 class="fw-bold text-dark mb-1">อัปโหลดไฟล์ข้อมูล</h3>
                            <p class="text-muted">เลือกโรงงานและข้อมูลเพื่อเริ่มต้นการวิเคราะห์ Forecast</p>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            <!-- Plant Selection -->
                            <div class="row align-items-center mb-4">
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-1">โรงงาน (Plant)</h6>
                                    <p class="text-muted small mb-0">เลือกโรงงานเป้าหมายเพื่อเริ่มวิเคราะห์ข้อมูล</p>
                                </div>
                                <div class="col-lg-6 mt-3 mt-lg-0">
                                    <select class="form-select form-select-lg border-0 bg-light rounded-4 shadow-sm select2-upload" name="plant" id="plant" data-placeholder="-- กรุณาเลือกโรงงาน --" required>
                                        <option value="">-- กรุณาเลือกโรงงาน --</option>
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
                            </div>

                            <!-- Customer Dropdown (Ajax) -->
                            <div class="row align-items-center mb-4">
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-1">ลูกค้า (Customer)</h6>
                                    <p class="text-muted small mb-0">ข้อมูลลูกค้าจาก SAP (โหลดอัตโนมัติตามโรงงาน)</p>
                                </div>
                                <div class="col-lg-6 mt-3 mt-lg-0">
                                    <div class="position-relative customer-select-wrap">
                                        <select class="form-select form-select-lg border-0 bg-light rounded-4 shadow-sm select2-upload" name="customer" id="customer" data-placeholder="-- กรุณาเลือกโรงงานก่อน --" disabled>
                                            <option value="">-- กรุณาเลือกโรงงานก่อน --</option>
                                        </select>
                                        <div id="customerSpinner" class="d-none position-absolute top-50 end-0 translate-middle-y pe-3">
                                            <span class="spinner-border spinner-border-sm text-secondary"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="drop-zone" id="dropZone">
                                <div class="text-center">
                                    <i class="fas fa-file-invoice-dollar mb-3"></i>
                                    <h5 class="fw-bold mb-2">ลากไฟล์มาวางที่นี่</h5>
                                    <p class="text-muted mb-0">หรือคลิกที่นี่เพื่อเลือกไฟล์จากคอมพิวเตอร์</p>
                                    <div id="fileInfo" class="mt-4 badge bg-primary bg-opacity-10 text-primary p-2 fs-6 rounded-pill d-none"></div>
                                </div>
                            </div>
                            <input type="file" name="forecast_file" id="fileInput" accept=".xlsx, .xls">

                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-4 py-3 fw-bold shadow-lg mt-4">
                                <i class="fas fa-microchip me-2"></i> เริ่มต้นการประมวลผล (Execute)
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Side: Guidelines & Status -->
                <div class="info-panel">
                    <div class="tip-card">
                        <h6 class="fw-bold text-dark mb-3 small"><i class="fas fa-lightbulb text-warning me-2"></i>PRE-UPLOAD TIPS</h6>
                        <ul class="list-unstyled mb-0 small text-secondary">
                            <li class="mb-2 d-flex align-items-start">
                                <i class="fas fa-check text-success mt-1 me-2"></i>
                                <span>Ensure the file format is <b>.xlsx</b> or <b>.xls</b> only.</span>
                            </li>
                            <li class="mb-2 d-flex align-items-start">
                                <i class="fas fa-check text-success mt-1 me-2"></i>
                                <span>Review the date format in your Excel before importing.</span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="fas fa-check text-success mt-1 me-2"></i>
                                <span>Plant code must be selected correctly for data routing.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="tip-card bg-primary bg-opacity-10 border-primary border-opacity-10">
                        <h6 class="fw-bold text-primary mb-2 small"><i class="fas fa-database me-2"></i>SYSTEM STATUS</h6>
                        <div class="d-flex align-items-center small mb-2">
                            <span class="status-badge bg-success"></span>
                            <span class="text-dark">Oracle Node: Connected</span>
                        </div>
                        <div class="d-flex align-items-center small">
                            <span class="status-badge bg-success"></span>
                            <span class="text-dark">Sync Engine: Ready (v2.0)</span>
                        </div>
                    </div>

                    <div class="tip-card cursor-pointer" onclick="window.location.href='list.php'">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-dark rounded-3 p-2 me-3">
                                    <i class="fas fa-history text-white small"></i>
                                </div>
                                <h6 class="fw-bold mb-0 small">RECENT ACTIVITY</h6>
                            </div>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end content-container -->
    </div> <!-- end main-wrapper -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        const dropZone = $('#dropZone');
        const fileInput = $('#fileInput');
        const fileInfo = $('#fileInfo');
        const plantSelect = $('#plant');
        const customerSelect = $('#customer');
        const spinner = $('#customerSpinner');

        plantSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: plantSelect.data('placeholder'),
            minimumResultsForSearch: 0
        });

        customerSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: customerSelect.data('placeholder'),
            minimumResultsForSearch: 0
        });

        function refreshCustomerSelect(html, disabled = true) {
            customerSelect.prop('disabled', disabled).html(html).val(null).trigger('change.select2');
        }

        dropZone.click(() => fileInput.click());

        // Ajax: Load Customers when Plant changes
        plantSelect.change(function() {
            const plant = $(this).val();

            refreshCustomerSelect('<option value="">Loading...</option>', true);

            if (!plant) {
                refreshCustomerSelect('<option value="">-- กรุณาเลือกโรงงานก่อน --</option>', true);
                return;
            }

            spinner.removeClass('d-none');

            $.ajax({
                url: 'get_customers_ajax.php',
                data: { plant: plant },
                // dataType: 'json', 
                success: function(response) {
                    customerSelect.empty().append('<option value="">-- Select Customer --</option>');
                    
                    if (response.startsWith('ERROR:')) {
                        customerSelect.append('<option value="">' + response.replace('ERROR:', '') + '</option>');
                    } else if (response.startsWith('EMPTY:')) {
                        customerSelect.append('<option value="">No customers found</option>');
                    } else {
                        const lines = response.split('\n');
                        lines.forEach(function(line) {
                            if (line.trim()) {
                                const parts = line.split('|||');
                                if (parts.length === 2) {
                                    customerSelect.append(`<option value="${parts[0]}">${parts[0]} - ${parts[1]}</option>`);
                                }
                            }
                        });
                        customerSelect.prop('disabled', false);
                    }
                    customerSelect.val(null).trigger('change.select2');
                },
                error: function(xhr) {
                    let raw = xhr.responseText ? xhr.responseText.substring(0, 100) : 'Unknown Error';
                    refreshCustomerSelect(`<option value="">Fail: ${raw}</option>`, true);
                },
                complete: function() {
                    spinner.addClass('d-none');
                }
            });
        });

        fileInput.change(function() {
            if(this.files.length > 0) {
                fileInfo.text("Selected: " + this.files[0].name).removeClass('d-none');
            }
        });

        dropZone.on('dragover', (e) => { 
            e.preventDefault(); 
            dropZone.addClass('drag-over'); 
        });
        dropZone.on('dragleave', () => dropZone.removeClass('drag-over'));
        dropZone.on('drop', (e) => {
            e.preventDefault();
            dropZone.removeClass('drag-over');
            const files = e.originalEvent.dataTransfer.files;
            if(files.length > 0) {
                fileInput[0].files = files;
                fileInfo.text("Selected: " + files[0].name).removeClass('d-none');
            }
        });

        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            const plant = $('#plant').val();
            const customer = $('#customer').val();
            const file = $('#fileInput')[0].files[0];
            
            if(!plant || !file) {
                return Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกโรงงานและเลือกไฟล์ข้อมูล', 'error');
            }

            // ใช้ FormData ดึงค่าจากฟอร์มโดยตรง
            let fd = new FormData(this);
            
            // ป้องกันฟิลด์ที่โดน disabled แล้ว FormData ดึงไม่ติด
            if (!fd.has('customer')) fd.append('customer', customer);
            if (!fd.has('plant')) fd.append('plant', plant);
            
            Swal.fire({
                title: 'Intelligence Syncing',
                html: '<div class="mt-3"><div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;"></div><p class="text-muted fw-bold">กำลังประมวลผลข้อมูล...</p></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: 'api/process_upload.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        const d = res.details;
                        Swal.fire({
                            title: 'บันทึกสำเร็จ',
                            icon: 'success',
                            html: `
                                <div class="text-start p-3 bg-light rounded-3 mt-2">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>รายการใหม่:</span>
                                        <b class="text-success">${d.new}</b>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>อัปเดตปริมาณ:</span>
                                        <b class="text-primary">${d.updated}</b>
                                    </div>
                                    <div class="d-flex justify-content-between mb-0">
                                        <span>รายการซ้ำ (ข้าม):</span>
                                        <b class="text-secondary">${d.duplicates}</b>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'ไปหน้าดูข้อมูล'
                        }).then(() => { window.location.href = 'forecast_view.php'; });
                    } else {
                        Swal.fire('บันทึกผิดพลาด', res.message, 'error');
                    }
                },
                error: function(xhr) { 
                    console.error("Server Error:", xhr.responseText);
                    Swal.fire('System Error', 'ไม่สามารถติดต่อ Server ได้', 'error'); 
                }
            });
        });
    });
</script>

</body>
</html>
