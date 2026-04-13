<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-life-ring"></i></div>
        <div class="sidebar-brand-text mx-3">RESCUE ADMIN</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item active">
        <a class="nav-link" href="dashboard.php"><i class="fas fa-fw fa-tachometer-alt"></i> <span>Tổng quan</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Quản lý thực địa</div>
    <li class="nav-item">
        <a class="nav-link" href="rescue_requests.php"><i class="fas fa-fw fa-exclamation-circle"></i> <span>Yêu cầu cứu hộ</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="rescue_teams.php"><i class="fas fa-fw fa-users"></i> <span>Đội cứu hộ</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Hệ thống</div>
    <li class="nav-item">
        <a class="nav-link" href="manage_users.php"><i class="fas fa-fw fa-user-cog"></i> <span>Quản lý tài khoản</span></a>
    </li>
    <li class="nav-item <?php echo ($currentPage == 'rescue_report.php') ? 'active' : ''; ?>">
    <a class="nav-link" href="rescue_report.php">
        <i class="fas fa-fw fa-chart-pie"></i>
        <span>Báo cáo Thống kê</span>
    </a>
</li>
</ul>