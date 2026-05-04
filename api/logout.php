<?php
// 1. Khởi động lại session để hệ thống biết đang làm việc với phiên nào
session_start();

// 2. Xóa toàn bộ các biến trong Session (user_id, username, role_name...)
session_unset();

// 3. Phá hủy hoàn toàn Session này trên máy chủ
session_destroy();

// 4. Điều hướng người dùng về lại trang chủ (màn hình đăng nhập ngoài cùng)
header("Location: ../web_portal.php");
exit;
?>