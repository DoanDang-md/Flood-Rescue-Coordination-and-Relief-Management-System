<?php
session_start();
require_once '../config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];

    // Lấy thông tin chi tiết của Đội cứu hộ (nếu có)
    $team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
    $member_count = isset($_POST['member_count']) ? (int)$_POST['member_count'] : 1;
    $equipment = isset($_POST['equipment']) ? trim($_POST['equipment']) : '';

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Thêm vào bảng users
        $stmtUser = $pdo->prepare("
            INSERT INTO users (role_id, username, password_hash, full_name, phone) 
            VALUES (:role_id, :username, :password_hash, :full_name, :phone)
        ");
        $stmtUser->execute([
            'role_id' => $role_id,
            'username' => $username,
            'password_hash' => $hashed_password,
            'full_name' => $full_name,
            'phone' => $phone
        ]);

        $new_user_id = $pdo->lastInsertId();

        // 2. Kiểm tra quyền
        $stmtRole = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $stmtRole->execute([$role_id]);
        $role_name = $stmtRole->fetchColumn();

        // Nếu là Đội cứu hộ thì thêm vào bảng rescue_teams và team_workload
       if ($role_id == 3) { 
            
            if (empty($team_name)) {
                $team_name = $full_name;
            }

            // 3. Thêm vào bảng rescue_teams
            $stmtTeam = $pdo->prepare("
                INSERT INTO rescue_teams (user_id, team_name, member_count, equipment, status) 
                VALUES (:user_id, :team_name, :member_count, :equipment, 'Available')
            ");

        $pdo->commit();

        $_SESSION['msg'] = "Đã tạo tài khoản và hồ sơ thành công cho: <strong>$full_name</strong>";
        $_SESSION['msg_type'] = "success";
        header("Location: ../../views/admin/manage_users.php");
        exit;

    } }catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { 
            $_SESSION['msg'] = "Lỗi: Tên đăng nhập hoặc Số điện thoại đã tồn tại!";
        } else {
            $_SESSION['msg'] = "Lỗi hệ thống: " . $e->getMessage();
        }
        $_SESSION['msg_type'] = "danger";
        header("Location: ../../views/admin/manage_users.php");
        exit;
    }
}
?>