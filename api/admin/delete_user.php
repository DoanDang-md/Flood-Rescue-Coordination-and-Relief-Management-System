<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../web_portal.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];

    // Chống việc ADmin tự xóa bản thân
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['msg'] = "Lỗi: Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập!";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../../views/admin/manage_users.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->execute([$user_id]);

        $_SESSION['msg'] = "Đã xóa vĩnh viễn tài khoản khỏi hệ thống.";
        $_SESSION['msg_type'] = "success";
    } catch (PDOException $e) {
        // Bắt lỗi ráng buộc dữ liệu (Ví dụ: Đội cứu hộ này đang nhận nhiệm vụ, không thể xóa)
        if ($e->getCode() == 23000) { 
            $_SESSION['msg'] = "Không thể xóa! Tài khoản này đang bị ràng buộc với dữ liệu Đội cứu hộ hoặc Điều phối viên trong hệ thống.";
        } else {
            $_SESSION['msg'] = "Lỗi hệ thống: " . $e->getMessage();
        }
        $_SESSION['msg_type'] = "warning";
    }
    header("Location: ../../views/admin/manage_users.php");
    exit;
}
?>