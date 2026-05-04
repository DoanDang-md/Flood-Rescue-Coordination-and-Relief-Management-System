<?php
session_start();
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../web_portal.php"); exit;
}
require_once '../../api/config.php';

try {
    // Thêm u.role_id vào câu lệnh SELECT
    $stmtUsers = $pdo->query("
        SELECT 
            u.user_id, u.username, u.full_name, u.phone, u.role_id, r.role_name,
            rt.team_id, rt.team_name, rt.member_count, rt.equipment
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN rescue_teams rt ON u.user_id = rt.user_id
        ORDER BY u.user_id DESC
    ");
    $users = $stmtUsers->fetchAll();

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
                        <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
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
                                            <td class="text-center align-middle"><?php echo $u['user_id']; ?></td>
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
                                               <button class="btn btn-info btn-sm btn-edit" 
    data-id="<?php echo $u['user_id']; ?>"
    data-username="<?php echo htmlspecialchars($u['username']); ?>"
    data-fullname="<?php echo htmlspecialchars($u['full_name']); ?>"
    data-phone="<?php echo htmlspecialchars($u['phone']); ?>"
    data-role="<?php echo $u['role_id']; ?>"
    data-teamname="<?php echo htmlspecialchars($u['team_name'] ?? ''); ?>"
    data-members="<?php echo htmlspecialchars($u['member_count'] ?? ''); ?>"
    data-equipment="<?php echo htmlspecialchars($u['equipment'] ?? ''); ?>"
    data-toggle="modal" data-target="#editUserModal" title="Sửa">
    <i class="fas fa-edit"></i>
</button>
                                                
                                                <button class="btn btn-danger btn-sm btn-delete" 
                                                    data-id="<?php echo $u['user_id']; ?>"
                                                    data-fullname="<?php echo htmlspecialchars($u['full_name']); ?>"
                                                    data-toggle="modal" data-target="#deleteUserModal" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="../../api/admin/create_user.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu</label>
                            <input type="password" class="form-control" name="password" value="123456" required>
                        </div>
                        <div class="form-group">
                            <label>Họ và Tên</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Quyền</label>
                            <select class="form-control" name="role_id" id="roleSelect" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['role_id']; ?>"><?php echo $r['role_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="rescueTeamFields" style="display: none; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 15px;">
    <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-life-ring mr-1"></i>Hồ sơ Đội Cứu Hộ</h6>
    <div class="form-group">
        <label>Tên Đội Cứu Hộ <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="team_name" id="input_team_name" placeholder="VD: Đội Canô Phản Ứng Nhanh">
    </div>
    <div class="form-group">
        <label>Số lượng thành viên</label>
        <input type="number" class="form-control" name="member_count" value="1" min="1">
    </div>
    <div class="form-group">
        <label>Trang thiết bị mang theo</label>
        <textarea class="form-control" name="equipment" rows="2" placeholder="VD: 2 áo phao, 1 xuồng cao su, túi y tế..."></textarea>
    </div>
</div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Tạo mới</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold">Cập nhật thông tin</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="../../api/admin/update_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_id">
                        
                        <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                            <small class="text-danger">Không được phép đổi Tên đăng nhập</small>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới (Bỏ trống nếu không muốn đổi)</label>
                            <input type="password" class="form-control" name="password" placeholder="Nhập pass mới...">
                        </div>
                        <div class="form-group">
                            <label>Họ và Tên</label>
                            <input type="text" class="form-control" name="full_name" id="edit_fullname" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="form-group">
                            <label>Quyền</label>
                            <select class="form-control" name="role_id" id="edit_role" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['role_id']; ?>"><?php echo $r['role_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="editRescueTeamFields" style="display: none; border-top: 1px dashed #ccc; padding-top: 15px; margin-top: 15px;">
    <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-life-ring mr-1"></i>Hồ sơ Đội Cứu Hộ</h6>
    <div class="form-group">
        <label>Tên Đội Cứu Hộ <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="team_name" id="edit_team_name" placeholder="VD: Đội Canô Phản Ứng Nhanh">
    </div>
    <div class="form-group">
        <label>Số lượng thành viên</label>
        <input type="number" class="form-control" name="member_count" id="edit_member_count" min="1">
    </div>
    <div class="form-group">
        <label>Trang thiết bị mang theo</label>
        <textarea class="form-control" name="equipment" id="edit_equipment" rows="2"></textarea>
    </div>
</div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button type="submit" class="btn btn-info">Cập nhật</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold">Xác nhận Xóa</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="../../api/admin/delete_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="del_id">
                        <p>Bạn có chắc chắn muốn xóa tài khoản của <strong id="del_name" class="text-danger"></strong> không?</p>
                        <p class="text-muted small">Lưu ý: Hành động này không thể hoàn tác.</p>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger">Xóa Vĩnh Viễn</button></div>
                </form>
            </div>
        </div>
    </div>

    
    
    <script>
        $(document).ready(function() {
            $('.btn-edit').on('click', function() {
        $('#edit_id').val($(this).data('id'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_fullname').val($(this).data('fullname'));
        $('#edit_phone').val($(this).data('phone'));
        $('#edit_role').val($(this).data('role'));
        
        // Đổ dữ liệu riêng của Đội cứu hộ (nếu có)
        $('#edit_team_name').val($(this).data('teamname'));
        $('#edit_member_count').val($(this).data('members'));
        $('#edit_equipment').val($(this).data('equipment'));

        // Kích hoạt hàm kiểm tra ẩn/hiện Form dựa trên role đang chọn
        $('#edit_role').trigger('change');
    });

    // --- Logic điều khiển ẩn/hiện Form Đội Cứu Hộ khi đổi Role trong Form SỬA ---
    $('#edit_role').on('change', function() {
        var selectedRoleText = $(this).find("option:selected").text().trim().toLowerCase();
        if (selectedRoleText.includes('cứu hộ') || selectedRoleText.includes('rescue')) {
            $('#editRescueTeamFields').slideDown();
            $('#edit_team_name').attr('required', true);
        } else {
            $('#editRescueTeamFields').slideUp();
            $('#edit_team_name').attr('required', false);
        }
    });

            $('.btn-delete').on('click', function() {
                $('#del_id').val($(this).data('id'));
                $('#del_name').text($(this).data('fullname'));
            });
        });
        $('#roleSelect').on('change', function() {
    var selectedRoleText = $(this).find("option:selected").text().trim().toLowerCase();
    
    // Nếu tên quyền có chứa chữ 'cứu hộ' (hoặc 'rescue')
    if (selectedRoleText.includes('cứu hộ') || selectedRoleText.includes('rescue')) {
        $('#rescueTeamFields').slideDown(); // Hiện form chi tiết
        $('#input_team_name').attr('required', true); // Bắt buộc nhập tên đội
    } else {
        $('#rescueTeamFields').slideUp(); // Ẩn form chi tiết
        $('#input_team_name').attr('required', false); // Bỏ bắt buộc
    }
});

// Kích hoạt sự kiện kiểm tra một lần ngay khi mở Modal thêm mới
$('#addUserModal').on('show.bs.modal', function () {
    $('#roleSelect').trigger('change');
});
    </script>
</body>
</html>