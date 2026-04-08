<?php
// Bắt buộc phải có hàm này ở dòng đầu tiên để hệ thống nhớ người dùng đã đăng nhập
session_start();

// Gọi file kết nối Cơ sở dữ liệu
require_once 'config.php';
$hash_chuan_xac = password_hash('123456', PASSWORD_DEFAULT);
$pdo->exec("UPDATE users SET password_hash = '$hash_chuan_xac' WHERE username = 'admin'");

// Kiểm tra xem dữ liệu có được gửi từ Form POST không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Lọc dữ liệu trống
    if (empty($username) || empty($password)) {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin!'); window.history.back();</script>";
        exit;
    }

    try {
        // Viết câu lệnh SQL lấy thông tin tài khoản và Tên quyền (JOIN 2 bảng users và roles)
        $stmt = $pdo->prepare("
            SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = :username
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // KIỂM TRA MẬT KHẨU
        // Dùng hàm password_verify() là chuẩn bảo mật cao nhất hiện nay
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Nếu đúng, lưu thông tin "thẻ thẻ căn cước" vào Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['full_name'] = $user['full_name'];

            // ĐIỀU HƯỚNG TỰ ĐỘNG THEO QUYỀN (Role-based Routing)
            if ($user['role_name'] === 'Admin') {
                // TV5 & TV6: Admin vào trang Quản lý
                header("Location: ../views/admin/dashboard.php");
            } elseif ($user['role_name'] === 'Dispatcher') {
                // TV2 & TV3: Điều phối viên vào trang Bản đồ
                header("Location: ../views/dispatcher/map.php");
            } elseif ($user['role_name'] === 'RescueTeam') {
                // TV4: Đội cứu hộ vào trang Nhận nhiệm vụ
                header("Location: ../views/rescuer/tasks.php");
            } else {
                echo "<script>alert('Quyền truy cập không hợp lệ!'); window.location.href = '../index.html';</script>";
            }
            exit; // Dừng mã nguồn lại sau khi điều hướng

        } else {
            // Sai tên đăng nhập hoặc mật khẩu
            echo "<script>
                    alert('Tên đăng nhập hoặc mật khẩu không chính xác!');
                    window.location.href = '../index.html';
                  </script>";
            exit;
        }

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
} else {
    // Nếu ai đó gõ trực tiếp url /api/login.php lên trình duyệt, đá họ về trang chủ
    header("Location: ../index.html");
    exit;
}
?>