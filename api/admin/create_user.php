<?php
session_start();
require_once '../config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Nhận dữ liệu từ Form gửi sang
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];

    // 2. BĂM MẬT KHẨU BẢO MẬT
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 3. Thực thi lệnh INSERT vào Database
        $stmt = $pdo->prepare("
            INSERT INTO users (role_id, username, password_hash, full_name, phone) 
            VALUES (:role_id, :username, :password_hash, :full_name, :phone)
        ");
        
        $stmt->execute([
            'role_id' => $role_id,
            'username' => $username,
            'password_hash' => $hashed_password,
            'full_name' => $full_name,
            'phone' => $phone
        ]);

        // Tạo thông báo thành công và đẩy về lại trang Quản lý
        $_SESSION['msg'] = "Đã tạo tài khoản thành công cho nhân sự: <strong>$full_name</strong>";
        $_SESSION['msg_type'] = "success";
        header("Location: ../../views/admin/manage_users.php");
        exit;

    } catch (PDOException $e) {
        // Xử lý lỗi trùng lặp (Tài khoản hoặc SĐT đã tồn tại)
        if ($e->getCode() == 23000) { 
            $_SESSION['msg'] = "Lỗi: Tên đăng nhập hoặc Số điện thoại đã tồn tại trong hệ thống!";
        } else {
            $_SESSION['msg'] = "Lỗi hệ thống: " . $e->getMessage();
        }
        $_SESSION['msg_type'] = "danger";
        header("Location: ../../views/admin/manage_users.php");
        exit;
    }
}
?>