<?php
// Gọi file kết nối (Nhớ sửa lại đường dẫn config.php cho đúng nếu cần)
require_once 'config.php'; 

try {
    // Tạo mã băm xịn cho pass 123456
    $mat_khau_chuan = '123456';
    $ma_hoa_xin = password_hash($mat_khau_chuan, PASSWORD_DEFAULT);

    // Ép cập nhật mã băm này cho TOÀN BỘ tài khoản trong hệ thống
    $sql = "UPDATE users SET password_hash = :hash";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $ma_hoa_xin]);

    echo "<h3 style='color: green;'>✅ ĐÃ CẬP NHẬT THÀNH CÔNG!</h3>";
    echo "Tất cả tài khoản (admin, dieuphoi, doi1, doi2) hiện đã được reset về chung một mật khẩu là: <b>123456</b><br>";
    echo "Bây giờ bạn có thể quay lại trang đăng nhập để vào hệ thống.";

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>