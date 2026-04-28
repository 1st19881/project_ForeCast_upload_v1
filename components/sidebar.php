<?php
// components/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Header -->
<div class="mobile-header d-lg-none">
    <div class="d-flex align-items-center">
        <div class="logo-box" style="width: 24px; height: 24px;"><i class="fas fa-chart-line text-white shadow-sm" style="font-size: 0.8rem;"></i></div>
        <span class="fw-bold text-dark mb-0 h6">FORECAST</span>
    </div>
    <button class="sidebar-toggle-btn" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="logo-box"><i class="fas fa-chart-line text-white"></i></div>
            <h5 class="mb-0 fw-bold text-white">FORECAST</h5>
        </div>
        <button class="btn btn-link text-white p-0 opacity-50 d-none d-lg-block" id="desktopToggle">
            <i class="fas fa-indent"></i>
        </button>
    </div>
    <div class="menu-list">
        <a href="index.php" class="menu-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-cloud-upload-alt"></i><span>Upload Data</span>
        </a>
        <a href="list.php" class="menu-item <?php echo ($current_page == 'list.php') ? 'active' : ''; ?>">
            <i class="fas fa-list-ul"></i><span>View Records</span>
        </a>
        <a href="forecast_view.php" class="menu-item <?php echo ($current_page == 'forecast_view.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i><span>Forecast Analytics</span>
        </a>

        <?php if (isset($_SESSION['aut_level']) && $_SESSION['aut_level'] == 99): ?>
        <div class="menu-divider"></div>
        <a href="#adminSubmenu" data-bs-toggle="collapse" class="menu-item d-flex justify-content-between align-items-center <?php echo ($current_page == 'manage_auth.php') ? 'active' : ''; ?>">
            <div><i class="fas fa-user-shield"></i><span>Admin Tools</span></div>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse <?php echo ($current_page == 'manage_auth.php') ? 'show' : ''; ?>" id="adminSubmenu">
            <a href="manage_auth.php" class="menu-item py-1 ps-4 <?php echo ($current_page == 'manage_auth.php') ? 'text-white fw-bold' : 'text-secondary'; ?>" style="font-size: 0.85rem;">
                <i class="fas fa-users-cog me-2"></i><span>Manage Auth</span>
            </a>
        </div>
        <?php endif; ?>

        <div class="menu-divider"></div>
        <!-- <a href="#" class="menu-item" onclick="Swal.fire('Coming Soon','Analytics board is under construction','info')">
            <i class="fas fa-chart-pie"></i><span>Analytics</span>
        </a> -->
        
        <a href="api/auth_logout.php" class="menu-item text-danger mt-auto">
            <i class="fas fa-sign-out-alt"></i><span>Sign Out</span>
        </a>
    </div>
    
    <div class="p-3 user-section">
        <div class="bg-dark rounded-4 p-3 user-info-card">
            <div class="d-flex align-items-center mb-2">
                <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; min-width: 32px;">
                    <i class="fas fa-user text-white small"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white small fw-bold text-truncate"><?php echo $_SESSION['fullname'] ?? $_SESSION['user_name']; ?></div>
                    <div class="text-secondary" style="font-size: 0.7rem;"><?php echo $_SESSION['user_code']; ?></div>
                </div>
            </div>
            <div class="text-secondary small mt-2 pt-2 border-top border-secondary border-opacity-25" style="font-size: 0.65rem;">
                v2.0 Professional Edition
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const desktopToggle = document.getElementById('desktopToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    const overlay = document.getElementById('sidebarOverlay');

    // Desktop Collapse
    if(desktopToggle) {
        desktopToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const icon = desktopToggle.querySelector('i');
            if(sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-outdent';
            } else {
                icon.className = 'fas fa-indent';
            }
        });
    }

    // Mobile Slide-in
    if(mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });
    }

    if(overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
});
</script>
