<?php
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') { header("Location: ../../index.html"); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Yêu cầu Tiếp nhận</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>
            
            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-inbox mr-2 text-primary"></i>Danh sách Yêu cầu Cứu hộ</h1>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tất cả yêu cầu từ hệ thống</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Người gửi</th>
                                        <th>SĐT</th>
                                        <th>Địa chỉ</th>
                                        <th>Mức độ</th>
                                        <th>Trạng thái</th>
                                        <th>Đội phụ trách</th>
                                    </tr>
                                </thead>
                                <tbody id="request-table-body">
                                    <tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<?php include 'layouts/core_scripts.php'; ?>

<script>
    async function loadTableData() {
        await fetchCoreData(); // Kêu Lõi gọi API
        
        const tbody = document.getElementById('request-table-body');
        tbody.innerHTML = ''; // Xóa dòng "Đang tải dữ liệu..."

        if (allRequests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Chưa có yêu cầu cứu hộ nào trong hệ thống.</td></tr>';
            return;
        }

        // Đổ dữ liệu ra bảng
        allRequests.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="align-middle">${fmtTime(r.created_at)}</td>
                <td class="align-middle font-weight-bold text-primary">${esc(r.citizen_name)}</td>
                <td class="align-middle">${esc(r.phone)}</td>
                <td class="align-middle">${esc(r.address_note)}</td>
                <td class="align-middle">${svBadge(r.severity)}</td>
                <td class="align-middle">${sBadge(r.status)}</td>
                <td class="align-middle">${r.team_name ? `<span class="text-success font-weight-bold"><i class="fas fa-truck-pickup mr-1"></i>${esc(r.team_name)}</span>` : '<span class="text-muted small">Chưa phân công</span>'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    document.addEventListener('DOMContentLoaded', () => { 
        loadTableData(); 
        setInterval(loadTableData, 10000); 
    });
</script>
</body>
</html>