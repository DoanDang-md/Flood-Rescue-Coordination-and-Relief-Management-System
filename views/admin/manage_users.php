<?php
session_start();
// Khóa bảo vệ: Chỉ Admin mới được vào
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html"); 
    exit;
}

require_once '../../api/config.php';

// 1. Lấy danh sách Người dùng để hiển thị ra Bảng
try {
    $stmtUsers = $pdo->query("
        SELECT u.user_id, u.username, u.full_name, u.phone, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY u.user_id DESC
    ");
    $users = $stmtUsers->fetchAll();

    // 2. Lấy danh sách Vai trò (Roles) để đổ vào thanh chọn (Dropdown) lúc Thêm mới
    $stmtRoles = $pdo->query("SELECT * FROM roles");
    $roles = $stmtRoles->fetchAll();

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý Nhân sự - Admin</title>
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

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Quản lý Tài khoản Nhân sự</h1>
                        <button class="d-none d-sm-inline-block btn btn-primary shadow-sm" data-toggle="modal" data-target="#addUserModal">
                            <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Thêm tài khoản mới
                        </button>
                    </div>

                    <?php if (isset($_SESSION['msg'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['msg']; 
                                unset($_SESSION['msg']); // Xóa sau khi hiển thị
                                unset($_SESSION['msg_type']);
                            ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users mr-2"></i>Danh sách hệ thống</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center">ID</th>
                                            <th>Tên đăng nhập</th>
                                            <th>Họ và Tên</th>
                                            <th>Số điện thoại</th>
                                            <th class="text-center">Quyền</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td class="text-center align-middle"><?php echo htmlspecialchars($u['user_id']); ?></td>
                                            <td class="align-middle text-primary font-weight-bold"><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td class="align-middle"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                            <td class="align-middle"><?php echo htmlspecialchars($u['phone']); ?></td>
                                            <td class="text-center align-middle">
                                                <?php
                                                if ($u['role_name'] == 'Admin') echo '<span class="badge badge-danger">Quản trị viên</span>';
                                                elseif ($u['role_name'] == 'Dispatcher') echo '<span class="badge badge-warning text-dark">Điều phối viên</span>';
                                                else echo '<span class="badge badge-success">Đội cứu hộ</span>';
                                                ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button class="btn btn-info btn-sm" title="Sửa"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-danger btn-sm" title="Khóa"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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

    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">Tạo tài khoản mới</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="../../api/admin/create_user.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tên đăng nhập (Viết liền, không dấu)</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu khởi tạo</label>
                            <input type="password" class="form-control" name="password" value="123456" required>
                            <small class="text-muted">Mặc định là 123456. Người dùng có thể tự đổi sau.</small>
                        </div>
                        <div class="form-group">
                            <label>Họ và Tên thực tế</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại liên hệ</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Phân quyền (Vai trò)</label>
                            <select class="form-control" name="role_id" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['role_id']; ?>"><?php echo $r['role_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary">Xác nhận tạo mới</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>