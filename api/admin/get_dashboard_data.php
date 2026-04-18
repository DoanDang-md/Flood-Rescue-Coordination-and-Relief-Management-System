<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền bảo mật
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Truy cập bị từ chối!"]);
    exit;
}


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


echo json_encode($data);
?>