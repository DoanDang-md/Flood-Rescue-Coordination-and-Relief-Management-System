<?php
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html"); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Cấu hình Biểu đồ Tròn (Doughnut Chart)
    var ctxPie = document.getElementById("statusPieChart");
    var statusPieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ["Chờ xử lý", "Đang cứu", "Đã xong"],
            datasets: [{
                data: [18, 5, 124], // Số liệu tạm (Sau này sẽ nối API lấy từ CSDL)
                backgroundColor: ['#e74a3b', '#f6c23e', '#1cc88a'], // Đỏ, Vàng, Xanh lá
                hoverBackgroundColor: ['#e02d1b', '#dda20a', '#17a673'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '70%',
        },
    });

    // 2. Cấu hình Biểu đồ Cột (Bar Chart)
    var ctxBar = document.getElementById("rescueBarChart");
    var rescueBarChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ["Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7", "CN"],
            datasets: [{
                label: "Số ca cứu hộ",
                backgroundColor: "#4e73df", // Màu xanh dương chuẩn Admin
                hoverBackgroundColor: "#2e59d9",
                borderColor: "#4e73df",
                data: [12, 19, 15, 25, 22, 30, 18], // Số liệu giả lập
                borderRadius: 4
            }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2] } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
<body id="page-top">
    <div id="wrapper">
        
        <?php include 'layouts/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                
                <?php include 'layouts/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Bảng điều khiển</h1>
                    <div class="row">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Yêu cầu mới</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="ui-new-requests">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Đội trực chiến</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="ui-active-teams">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ca thành công</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="ui-completed-cases">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Lượng Yêu cầu Cứu hộ (7 ngày qua)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px;">
                    <canvas id="rescueBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Tỉ lệ Trạng thái</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                    <canvas id="statusPieChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2"><i class="fas fa-circle text-danger"></i> Chờ xử lý</span>
                    <span class="mr-2"><i class="fas fa-circle text-warning"></i> Đang cứu</span>
                    <span class="mr-2"><i class="fas fa-circle text-success"></i> Đã xong</span>
                </div>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Dùng Fetch API gọi Backend lấy dữ liệu JSON
            fetch('../../api/admin/get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        // Đổ dữ liệu từ BE lên các ID của FE
                        document.getElementById('ui-new-requests').innerText = data.stats.new_requests;
                        document.getElementById('ui-active-teams').innerText = data.stats.active_teams;
                        document.getElementById('ui-completed-cases').innerText = data.stats.completed_cases;
                    }
                })
                .catch(error => console.error('Lỗi khi gọi API:', error));
        });
    </script>
</body>
</html>