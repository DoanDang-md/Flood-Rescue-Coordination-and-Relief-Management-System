<?php
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') { header("Location: ../../index.html"); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Trạng thái Đội cứu hộ</title>
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
                <h1 class="h3 mb-4 text-gray-800"><i class="fas fa-users-cog mr-2 text-info"></i>Trạng thái Đội Cứu hộ</h1>
                <div class="row" id="team-grid">
                    <div class="col-12"><div class="alert alert-secondary">Đang tải trạng thái các đội...</div></div>
                </div>
            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>
</body>
</html>