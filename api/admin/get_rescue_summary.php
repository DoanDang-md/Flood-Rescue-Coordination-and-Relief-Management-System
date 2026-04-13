<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Bảo mật: Chỉ Admin mới được xem báo cáo
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["error" => "Truy cập bị từ chối!"]);
    exit;
}

try {
    // Sửa lại tên cột: dùng r.request_id thay vì r.id
    // Sửa lại trạng thái: Dùng 'Hoàn thành' thay cho 'success'. Do DB của ta không có 'failed', 
    // tôi sẽ gom các ca 'Mới', 'Đang điều phối', 'Đang cứu hộ' thành nhóm 'Đang xử lý'.
    $sql = "
        SELECT 
            r.status,
            COUNT(r.request_id) AS total_cases
        FROM rescue_requests r
        GROUP BY r.status
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    $successCount = 0;
    $processingCount = 0;

    foreach ($results as $row) {
        if ($row['status'] === 'Hoàn thành') {
            $successCount += (int)$row['total_cases'];
        } else {
            $processingCount += (int)$row['total_cases'];
        }
    }

    $totalCases = $successCount + $processingCount;
    $successRate = $totalCases === 0 ? 0 : ($successCount / $totalCases) * 100;

    // Trả về JSON giữ nguyên cấu trúc của TV6 để không làm vỡ Frontend (tạm đổi failed thành pending)
    echo json_encode([
        "success" => [
            "count" => $successCount, 
            "workload_hours" => $successCount * 2.5 // Tạm tính trung bình 2.5h/ca vì DB gốc chưa có log chi tiết giờ
        ],
        "failed" => [ // Đổi nhãn Failed thành Đang xử lý ở FE sau
            "count" => $processingCount, 
            "workload_hours" => 0
        ],
        "total_cases" => $totalCases,
        "success_rate" => $successRate
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Lỗi CSDL: " . $e->getMessage()]);
}
?>