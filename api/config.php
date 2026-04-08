<?php
// Bật chế độ hiển thị lỗi (rất cần thiết trong lúc Dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$dbname = 'flood_rescue_db';
$username = 'root'; 
$password = '632005';    

// Chuỗi kết nối DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// Cấu hình các tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi nghiêm ngặt nếu SQL sai
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về dữ liệu dạng mảng Key-Value (Dễ chuyển thành JSON)
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt giả lập prepare để bảo mật tuyệt đối
];

try {
    // Khởi tạo đối tượng kết nối PDO
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Test thử kết nối (Bạn có thể bỏ comment dòng echo dưới đây để test, sau khi chạy OK thì comment lại)
    // echo json_encode(["status" => "success", "message" => "Kết nối Database thành công!"]);

} catch (PDOException $e) {
    // Nếu kết nối thất bại, trả về lỗi chuẩn JSON cho Frontend
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500); // Lỗi Server
    echo json_encode([
        "success" => false,
        "message" => "Lỗi kết nối Cơ sở dữ liệu!",
        "error_detail" => $e->getMessage() // Chi tiết lỗi để nhóm dễ fix
    ]);
    exit; // Dừng toàn bộ chương trình nếu không có DB
}
?>