<?php
session_start();
if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['Admin', 'Dispatcher'])) {
    header("Location: ../../web_portal.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tổng Quan Thống Kê Điều Phối</title>

    <!-- SB Admin 2 + FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        .disp-stat-card { border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 18px; box-shadow: 0 4px 10px rgba(0,0,0,.05); border-left: 5px solid; }
        .disp-stat-icon { width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; }
        .disp-stat-val { font-size: 32px; font-weight: 800; line-height: 1; }
        .disp-stat-lbl { font-size: 13px; color: #858796; font-weight: 600; margin-top: 4px; text-transform: uppercase; }
        .border-primary { border-color: #4e73df !important; }
        .border-danger { border-color: #e74a3b !important; }
        .border-warning { border-color: #f6c23e !important; }
        .border-success { border-color: #1cc88a !important; }
        .chart-area { height: 320px; position: relative; }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>

            <div class="container-fluid mt-4">
                <!-- Tiêu đề (Đã bỏ nút Điều phối) -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line mr-2 text-primary"></i>Báo Cáo Thống Kê Trực Tuyến</h1>
                </div>

                <!-- 4 THẺ SỐ LIỆU TỔNG QUAN -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="disp-stat-card bg-white border-primary">
                            <div class="disp-stat-icon bg-primary text-white"><i class="fas fa-inbox"></i></div>
                            <div><div class="disp-stat-val text-primary" id="stat-total">0</div><div class="disp-stat-lbl">TỔNG CA ĐANG XỬ LÝ</div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="disp-stat-card bg-white border-danger">
                            <div class="disp-stat-icon bg-danger text-white"><i class="fas fa-exclamation-circle"></i></div>
                            <div><div class="disp-stat-val text-danger" id="stat-red">0</div><div class="disp-stat-lbl">MỨC ĐỘ KHẨN CẤP</div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="disp-stat-card bg-white border-warning">
                            <div class="disp-stat-icon bg-warning text-white"><i class="fas fa-exclamation-triangle"></i></div>
                            <div><div class="disp-stat-val text-warning" id="stat-yellow">0</div><div class="disp-stat-lbl">MỨC ĐỘ TRUNG BÌNH</div></div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="disp-stat-card bg-white border-success">
                            <div class="disp-stat-icon bg-success text-white"><i class="fas fa-truck-pickup"></i></div>
                            <div><div class="disp-stat-val text-success" id="stat-teams">0</div><div class="disp-stat-lbl">ĐỘI SẴN SÀNG</div></div>
                        </div>
                    </div>
                </div>

                <!-- BIỂU ĐỒ (Dùng số liệu thực) -->
                <div class="row">
                    <!-- Biểu đồ phân bổ mức độ -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Mức Độ Nghiêm Trọng (Hiện tại)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area pt-2">
                                    <canvas id="severityPieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ khối lượng công việc -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Tải Trọng Công Việc Các Đội (Ca đang xử lý)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area pt-2">
                                    <canvas id="workloadBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let severityPieChart, workloadBarChart;

    // Khởi tạo khung biểu đồ (Chưa có dữ liệu)
    function initCharts() {
        // 1. Biểu đồ Tròn (Pie)
        const ctxPie = document.getElementById('severityPieChart').getContext('2d');
        severityPieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Khẩn cấp', 'Cao', 'Trung bình', 'Thấp'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#e74a3b', '#fd7e14', '#f6c23e', '#1cc88a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)"
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '60%'
            }
        });

        // 2. Biểu đồ Cột (Bar)
        const ctxBar = document.getElementById('workloadBarChart').getContext('2d');
        workloadBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: [], // Tên các đội
                datasets: [{
                    label: 'Số ca đang xử lý',
                    backgroundColor: '#4e73df',
                    data: [], // Số lượng ca
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Hàm gọi API và đẩy dữ liệu thật vào biểu đồ
    async function fetchDashboardStats() {
        try {
            const [reqRes, teamRes] = await Promise.all([
                fetch('../../api/dispatcher/get_requests.php').then(r => r.json()),
                fetch('../../api/dispatcher/get_suggested_teams.php').then(r => r.json())
            ]);

            const reqs = reqRes.success ? reqRes.data : [];
            const teams = teamRes.success ? teamRes.data : [];

            // Chỉ lọc các ca CHƯA hoàn thành
            const activeReqs = reqs.filter(r => r.status !== 'Hoàn thành');

            // 1. CẬP NHẬT 4 THẺ SỐ LIỆU
            document.getElementById('stat-total').textContent  = activeReqs.length;
            document.getElementById('stat-red').textContent    = activeReqs.filter(r => r.severity === 'Critical').length;
            document.getElementById('stat-yellow').textContent = activeReqs.filter(r => r.severity === 'Medium').length;
            document.getElementById('stat-teams').textContent  = teams.filter(t => t.status === 'Available').length;

            // 2. CẬP NHẬT BIỂU ĐỒ TRÒN (Phân bố mức độ)
            const countCritical = activeReqs.filter(r => r.severity === 'Critical').length;
            const countHigh     = activeReqs.filter(r => r.severity === 'High').length;
            const countMedium   = activeReqs.filter(r => r.severity === 'Medium').length;
            const countLow      = activeReqs.filter(r => r.severity === 'Low').length;

            severityPieChart.data.datasets[0].data = [countCritical, countHigh, countMedium, countLow];
            severityPieChart.update();

            // 3. CẬP NHẬT BIỂU ĐỒ CỘT (Tải trọng đội)
            const teamLabels = [];
            const teamWorkloads = [];
            
            teams.forEach(t => {
                teamLabels.push(t.team_name);
                teamWorkloads.push(t.current_active);
            });

            workloadBarChart.data.labels = teamLabels;
            workloadBarChart.data.datasets[0].data = teamWorkloads;
            workloadBarChart.update();

        } catch (error) {
            console.error("Lỗi khi tải dữ liệu thống kê", error);
        }
    }

    // Khởi chạy khi load xong HTML
    document.addEventListener('DOMContentLoaded', () => {
        initCharts();
        fetchDashboardStats();
        // Tự động quét dữ liệu mới mỗi 15 giây để biểu đồ cập nhật "sống" (live)
        setInterval(fetchDashboardStats, 15000); 
    });
</script>
</body>
</html>