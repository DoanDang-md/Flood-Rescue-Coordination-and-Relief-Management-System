<?php
session_start();
// Cho phép cả Admin và Dispatcher xem trang này
if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['Admin', 'Dispatcher'])) {
    header("Location: ../../web_portal.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trạng thái Đội cứu hộ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>
            
            <div class="container-fluid mt-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users-cog mr-2 text-info"></i>Trạng thái Đội Cứu hộ</h1>
                    <button class="btn btn-sm btn-outline-info shadow-sm" onclick="loadTeams()">
                        <i class="fas fa-sync-alt fa-sm mr-1"></i> Làm mới dữ liệu
                    </button>
                </div>

                <!-- Lưới hiển thị danh sách Đội cứu hộ -->
                <div class="row" id="team-grid">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-info mb-3"></i>
                        <p class="text-muted">Đang tải trạng thái các đội...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>



<script>
    const API_URL = '../../api/dispatcher/get_suggested_teams.php';

    // Hàm chống XSS khi hiển thị dữ liệu
    function esc(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    async function loadTeams() {
        const grid = document.getElementById('team-grid');
        
        try {
            const response = await fetch(API_URL);
            const data = await response.json();

            grid.innerHTML = ''; // Xóa trạng thái loading

            if (data.success && data.data.length > 0) {
                data.data.forEach(team => {
                    // Cài đặt màu sắc theo trạng thái
                    const isAvailable = (team.status === 'Available');
                    const borderColor = isAvailable ? 'success' : 'warning';
                    const textColor   = isAvailable ? 'success' : 'warning';
                    const statusText  = isAvailable ? '🟢 Sẵn sàng' : '🟡 Đang bận';
                    const iconBounce  = isAvailable ? '' : 'fa-beat-fade'; // Hiệu ứng nháy nếu đang bận

                    // Tạo card cho mỗi đội
                    const col = document.createElement('div');
                    col.className = 'col-xl-4 col-md-6 mb-4';
                    col.innerHTML = `
                        <div class="card border-left-${borderColor} shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-${textColor} text-uppercase mb-1">
                                            ${statusText}
                                        </div>
                                        <div class="h5 mb-2 font-weight-bold text-gray-800">${esc(team.team_name)}</div>
                                        
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-user-tie fa-fw"></i> <strong>Đội trưởng:</strong> ${esc(team.leader_name)} (${esc(team.contact_phone)})
                                        </div>
                                        <div class="small text-muted mb-3">
                                            <i class="fas fa-tools fa-fw"></i> <strong>Sức chứa:</strong> ${team.member_count} TV | <strong>Trang bị:</strong> ${esc(team.equipment || 'Cơ bản')}
                                        </div>
                                        
                                        <!-- Khối lượng công việc -->
                                        <div class="d-flex justify-content-between text-center bg-light rounded p-2 border">
                                            <div>
                                                <div class="font-weight-bold text-danger">${team.current_active}</div>
                                                <div class="small text-muted">Đang xử lý</div>
                                            </div>
                                            <div class="border-left"></div>
                                            <div>
                                                <div class="font-weight-bold text-success">${team.completed_cases}</div>
                                                <div class="small text-muted">Hoàn thành</div>
                                            </div>
                                            <div class="border-left"></div>
                                            <div>
                                                <div class="font-weight-bold text-primary">${team.total_assigned}</div>
                                                <div class="small text-muted">Tổng nhận</div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="col-auto pl-3">
                                        <i class="fas fa-truck-pickup fa-2x text-gray-300 ${iconBounce}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    grid.appendChild(col);
                });
            } else {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có đội cứu hộ nào khả dụng trong hệ thống.</h5>
                    </div>`;
            }
        } catch (error) {
            console.error("Lỗi khi tải dữ liệu Đội cứu hộ:", error);
            grid.innerHTML = `
                <div class="col-12 text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Lỗi kết nối tới máy chủ!
                </div>`;
        }
    }

    // Khởi chạy khi tải trang và tự động cập nhật mỗi 10 giây
    document.addEventListener('DOMContentLoaded', () => {
        loadTeams();
        setInterval(loadTeams, 10000); 
    });
</script>
</body>
</html>