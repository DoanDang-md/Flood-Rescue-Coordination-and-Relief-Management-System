<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. Kiểm tra quyền bảo mật
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Truy cập bị từ chối!"]);
    exit;
}

// require_once '../config.php'; // Gọi DB thật vào đây (Tạm thời tôi comment lại để mô phỏng)

// 2. Thực hiện truy vấn logic (Ví dụ: đếm số lượng)
// $stmt = $pdo->query("SELECT COUNT(*) FROM rescue_requests WHERE status = 'Mới'");
// $new_requests = $stmt->fetchColumn();

// MOCK DATA (Dữ liệu giả lập để FE hiển thị trước khi ráp DB thật)
$data = [
    "status" => "success",
    "stats" => [
        "new_requests" => 18,
        "active_teams" => 5,
        "completed_cases" => 124
    ],
    "recent_activities" => [
        ["time" => "10:30", "action" => "Admin đã tạo tài khoản cho Đội 01", "status" => "Thành công"],
        ["time" => "09:15", "action" => "Điều phối viên nhận ca khẩn cấp", "status" => "Đang xử lý"]
    ]
];

// 3. Trả về Frontend dưới dạng JSON
echo json_encode($data);
?>