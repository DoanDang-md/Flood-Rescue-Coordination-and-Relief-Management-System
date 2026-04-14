<?php
/**
 * API: Lấy danh sách nhiệm vụ của đội cứu hộ đang đăng nhập
 * Method: GET
 * Return: JSON
 */

// Bật CORS và set header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include config dùng chung của nhóm
require_once __DIR__ . '/../config.php';

// Bắt đầu session (đã có trong config.php hoặc tự start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    // Lấy team_id của user hiện tại từ bảng rescue_teams
    $stmt = $conn->prepare("
        SELECT team_id 
        FROM rescue_teams 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $team = $result->fetch_assoc();
    
    if (!$team) {
        http_response_code(403);
        echo json_encode(['error' => 'Tài khoản chưa được gán vào đội cứu hộ']);
        exit;
    }
    
    $team_id = $team['team_id'];
    
    // Lấy danh sách nhiệm vụ CHƯA HOÀN THÀNH
    $sql = "
        SELECT 
            m.mission_id,
            m.status AS mission_status,
            m.assigned_at,
            m.note_from_team,
            rq.request_id,
            rq.citizen_name,
            rq.phone,
            rq.address_note,
            rq.latitude,
            rq.longitude,
            rq.severity,
            rq.description,
            rq.number_of_people
        FROM missions m
        JOIN rescue_requests rq ON m.request_id = rq.request_id
        WHERE m.team_id = ?
          AND m.status != 'Hoàn thành'
        ORDER BY 
            CASE m.status 
                WHEN 'Đang cứu hộ' THEN 1 
                WHEN 'Đang di chuyển' THEN 2
                ELSE 3
            END,
            m.assigned_at ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $missions = [];
    while ($row = $result->fetch_assoc()) {
        $missions[] = $row;
    }
    
    echo json_encode($missions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}
?>