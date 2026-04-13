<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../index.html"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            // Nếu có nhập pass mới thì Băm và Cập nhật
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, role_id=?, password_hash=? WHERE user_id=?");
            $stmt->execute([$full_name, $phone, $role_id, $hashed, $user_id]);
        } else {
            // Nếu để trống pass, giữ nguyên pass cũ
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, role_id=? WHERE user_id=?");
            $stmt->execute([$full_name, $phone, $role_id, $user_id]);
        }

        $_SESSION['msg'] = "Cập nhật thành công tài khoản: <strong>$full_name</strong>";
        $_SESSION['msg_type'] = "info";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { 
            $_SESSION['msg'] = "Lỗi: Số điện thoại này đã được sử dụng cho tài khoản khác!";
        } else {
            $_SESSION['msg'] = "Lỗi hệ thống: " . $e->getMessage();
        }
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: ../../views/admin/manage_users.php");
    exit;
}
?>