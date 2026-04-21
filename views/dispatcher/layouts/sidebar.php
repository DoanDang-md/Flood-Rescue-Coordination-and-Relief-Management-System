<ul class="navbar-nav bg-gradient-dark sidebar sidebar-dark accordion" id="accordionSidebar">
    
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-headset"></i> </div>
        <div class="sidebar-brand-text mx-3">DISPATCHER <sup>HQ</sup></div>
    </a>

    <hr class="sidebar-divider my-0">

    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

    <li class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Tổng quan Tác chiến</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Trung tâm Chỉ huy</div>

    <li class="nav-item <?php echo ($currentPage == 'live_map.php') ? 'active' : ''; ?>">
        <a class="nav-link text-warning font-weight-bold" href="live_map.php">
            <i class="fas fa-fw fa-map-marked-alt"></i>
            <span>Bản đồ Trực chiến</span>
        </a>
    </li>

    <li class="nav-item <?php echo ($currentPage == 'manage_requests.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="manage_requests.php">
            <i class="fas fa-fw fa-inbox"></i>
            <span>Yêu cầu tiếp nhận</span>
            <span class="badge badge-danger badge-counter float-right mt-1">Mới</span>
        </a>
    </li>

    <li class="nav-item <?php echo ($currentPage == 'dispatch_missions.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="dispatch_missions.php">
            <i class="fas fa-fw fa-clipboard-list"></i>
            <span>Lệnh Điều động</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Nguồn lực & Hậu cần</div>

    <li class="nav-item <?php echo ($currentPage == 'team_status.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="team_status.php">
            <i class="fas fa-fw fa-users-cog"></i>
            <span>Trạng thái Đội cứu hộ</span>
        </a>
    </li>

</ul>