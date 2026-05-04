<?php
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html"); 
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo cáo Cứu hộ - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        
        <?php include 'layouts/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                
                <?php include 'layouts/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-chart-bar mr-2"></i>Báo cáo Hoạt động Cứu hộ</h1>

                    <div class="row" id="stats-container">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ca Thành Công</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-success">Đang tải...</div>
                                            <div class="text-xs text-muted mt-1" id="stat-success-hours">Khối lượng: 0 giờ</div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Đang Xử Lý</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-processing">Đang tải...</div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-spinner fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tỷ lệ Thành công</div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="stat-rate">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-percent fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tương quan trạng thái</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                                        <canvas id="rescueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Danh sách Yêu cầu chi tiết</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover" id="detailsTable" width="100%" cellspacing="0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Người yêu cầu</th>
                                                    <th>Vị trí (Ghi chú)</th>
                                                    <th>Đội phụ trách</th>
                                                    <th>Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                </tbody>
                                        </table>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Sửa đường dẫn API trỏ thẳng vào file PHP của hệ thống
            const API_SUMMARY = '../../api/admin/get_rescue_summary.php';
            const API_DETAILS = '../../api/admin/get_rescue_details.php';

            // 1. Hàm lấy Thống kê tổng quan
            async function fetchSummary() {
                try {
                    const res = await fetch(API_SUMMARY);
                    const data = await res.json();
                    
                    if (data.error) {
                        console.error("Lỗi từ server:", data.error);
                        return;
                    }

                    // Đổ số liệu vào các thẻ HTML giao diện Bootstrap
                    document.getElementById('stat-success').innerText = `${data.success.count} ca`;
                    document.getElementById('stat-success-hours').innerText = `Khối lượng: ${data.success.workload_hours} giờ`;
                    document.getElementById('stat-processing').innerText = `${data.failed.count} ca`; // failed bây giờ mang ý nghĩa là đang xử lý
                    document.getElementById('stat-rate').innerText = `${data.success_rate.toFixed(1)}%`;

                    // Vẽ biểu đồ Chart.js (Chuyển thành Doughnut cho đẹp mắt theo chuẩn Admin)
                    const ctx = document.getElementById('rescueChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hoàn thành', 'Đang xử lý'],
                            datasets: [{
                                data: [data.success.count, data.failed.count],
                                backgroundColor: ['#1cc88a', '#f6c23e'], // Xanh lá và Vàng Bootstrap
                                hoverBackgroundColor: ['#17a673', '#dda20a'],
                                hoverBorderColor: "rgba(234, 236, 244, 1)"
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom' } },
                            cutout: '70%'
                        }
                    });
                } catch (error) {
                    console.error("Lỗi khi lấy Thống kê:", error);
                }
            }

            // 2. Hàm lấy Bảng chi tiết
            async function fetchDetails() {
                try {
                    const res = await fetch(API_DETAILS);
                    const rows = await res.json();
                    
                    if (rows.error) {
                        console.error("Lỗi từ server:", rows.error);
                        return;
                    }

                    const tbody = document.querySelector('#detailsTable tbody');
                    tbody.innerHTML = '';
                    
                    rows.forEach(row => {
                        // Xét màu trạng thái bằng Bootstrap class
                        let statusBadge = '';
                        if (row.status === 'Hoàn thành') {
                            statusBadge = `<span class="badge badge-success">${row.status}</span>`;
                        } else {
                            statusBadge = `<span class="badge badge-warning text-dark">${row.status}</span>`;
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="align-middle text-center">${row.id}</td>
                            <td class="align-middle font-weight-bold text-primary">${row.citizen_name || 'N/A'}</td>
                            <td class="align-middle">${row.location || 'N/A'}</td>
                            <td class="align-middle">${row.team_name || '<em class="text-muted">Chưa phân công</em>'}</td>
                            <td class="align-middle text-center">${statusBadge}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } catch (error) {
                    console.error("Lỗi khi lấy Bảng chi tiết:", error);
                }
            }

            // Khởi chạy 2 hàm khi load trang
            fetchSummary();
            fetchDetails();
        });
    </script>
</body>
</html>