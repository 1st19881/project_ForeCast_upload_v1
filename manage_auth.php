<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// ตรวจสอบสิทธิ์ (เฉพาะ Admin Level 99)
if (!isset($_SESSION['user_code'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['aut_level']) || $_SESSION['aut_level'] < 99) {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='index.php';</script>";
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_code = $_SESSION['user_code'] ?? '';
$fullname = $_SESSION['fullname'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Permissions - Forecast Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        /* Page-specific styles only */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
        }

        .btn-primary:hover {
            background-color: #bb2d3b;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-user { background: #dcfce7; color: #166534; }

        @media (max-width: 767.98px) {
            .header-section {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 1rem;
            }

            .header-section .btn {
                width: 100%;
            }

            .modal-dialog {
                margin: .75rem;
            }

            .modal-body,
            .modal-header {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="header-section d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Manage Permissions</h3>
                <p class="text-muted">ควบคุมสิทธิ์การเข้าใช้งานระบบ Forecast Management</p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-user-plus me-2"></i> Add New User
            </button>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="authTable">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="ps-4">Employee</th>
                                <th>Privilege</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="authList">
                            <!-- Data will be loaded here -->
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-danger" role="status"></div>
                                    <p class="mt-2 text-muted">กำลังโหลดข้อมูลสิทธิ์...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="authModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 overflow-hidden" style="border-radius: 20px;">
                <div class="modal-header border-0 bg-light p-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="authForm">
                        <input type="hidden" id="auth_id" name="auth_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-600">รหัสพนักงาน (CODEMPID)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codempid" name="codempid" placeholder="ตัวอย่าง: B167015" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="lookupEmployee()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control bg-light" id="aut_name" name="aut_name" readonly>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-600">ประเภทสิทธิ์</label>
                                <select class="form-select" id="aut_level" name="aut_level" onchange="updateAutType()">
                                    <option value="9">User (9)</option>
                                    <option value="99">Admin (99)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-600">Level Name</label>
                                <input type="text" class="form-control bg-light" id="aut_type" name="aut_type" value="User" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">สถานะ</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="aut_active" name="aut_active" checked>
                                <label class="form-check-label" for="aut_active">เปิดใช้งาน (Active)</label>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            loadAuthData();

            $('#authForm').on('submit', function(e) {
                e.preventDefault();
                saveAuth();
            });
        });

        function loadAuthData() {
            $.ajax({
                url: 'api/manage_auth.php',
                type: 'GET',
                data: { action: 'list' },
                success: function(response) {
                    if(response.status === 'success') {
                        renderList(response.data);
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }

        function renderList(data) {
            let html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลผู้ใช้งาน</td></tr>';
            } else {
                data.forEach(item => {
                    const levelBadge = item.AUT_LEVEL == 99 ? 'badge-admin' : 'badge-user';
                    const activeClass = item.AUT_ACTIVE === 'Y' ? 'text-success' : 'text-danger';
                    const activeLabel = item.AUT_ACTIVE === 'Y' ? 'Active' : 'Inactive';
                    
                    html += `
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light text-dark rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border: 1px solid #ddd;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">${item.AUT_NAME}</div>
                                        <small class="text-muted">${item.CODEMPID}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-uppercase small fw-bold text-muted">${item.AUT_PRIVILEGE}</span></td>
                            <td><span class="status-badge ${levelBadge}">${item.AUT_TYPE} (${item.AUT_LEVEL})</span></td>
                            <td><span class="${activeClass}"><i class="fas fa-circle ms-1 small me-2"></i>${activeLabel}</span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-secondary me-1" onclick='editAuth(${JSON.stringify(item)})'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAuth('${item.AUT_ID}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#authList').html(html);
        }

        function openAddModal() {
            $('#modalTitle').text('Add Permission');
            $('#authForm')[0].reset();
            $('#auth_id').val('');
            $('#codempid').prop('readonly', false);
            $('#aut_active').prop('checked', true);
            updateAutType();
            $('#authModal').modal('show');
        }

        function editAuth(item) {
            $('#modalTitle').text('Edit Permission');
            $('#auth_id').val(item.AUT_ID);
            $('#codempid').val(item.CODEMPID).prop('readonly', true);
            $('#aut_name').val(item.AUT_NAME);
            $('#aut_level').val(item.AUT_LEVEL);
            $('#aut_type').val(item.AUT_TYPE);
            $('#aut_active').prop('checked', item.AUT_ACTIVE === 'Y');
            $('#authModal').modal('show');
        }

        function updateAutType() {
            const level = $('#aut_level').val();
            $('#aut_type').val(level == 99 ? 'Admin' : 'User');
        }

        function lookupEmployee() {
            const code = $('#codempid').val().trim();
            if(!code) return;

            $.ajax({
                url: 'api/manage_auth.php',
                type: 'GET',
                data: { action: 'lookup', codempid: code },
                success: function(response) {
                    if(response.status === 'success') {
                        $('#aut_name').val(response.fullname);
                    } else {
                        Swal.fire('Not Found', 'ไม่พบรายชื่อพนักงานในระบบ', 'warning');
                        $('#aut_name').val('');
                    }
                }
            });
        }

        function saveAuth() {
            const formData = {
                action: 'save',
                auth_id: $('#auth_id').val(),
                codempid: $('#codempid').val(),
                aut_name: $('#aut_name').val(),
                aut_level: $('#aut_level').val(),
                aut_type: $('#aut_type').val(),
                aut_active: $('#aut_active').is(':checked') ? 'Y' : 'N'
            };

            if(!formData.aut_name) {
                Swal.fire('Warning', 'กรุณากดแว่นขยายเพื่อค้นหารายชื่อพนักงานก่อน', 'warning');
                return;
            }

            $.ajax({
                url: 'api/manage_auth.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if(response.status === 'success') {
                        $('#authModal').modal('hide');
                        Swal.fire('Success', 'บันทึกข้อมูลเรียบร้อย', 'success');
                        loadAuthData();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }

        function deleteAuth(id) {
            Swal.fire({
                title: 'Delete permission?',
                text: "ต้องการลบสิทธิ์พนักงานคนนี้ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/manage_auth.php',
                        type: 'POST',
                        data: { action: 'delete', auth_id: id },
                        success: function(response) {
                            if(response.status === 'success') {
                                Swal.fire('Deleted!', 'ลบเรียบร้อยแล้ว', 'success');
                                loadAuthData();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Sign Out?',
                text: "ต้องการออกจากระบบใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sign Out'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'api/auth_logout.php';
                }
            });
        }
    </script>
</body>
</html>
