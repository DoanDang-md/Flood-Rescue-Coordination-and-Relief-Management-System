<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    // 1. Lấy team_id bằng PDO
    $stmtTeam = $pdo->prepare("SELECT team_id FROM rescue_teams WHERE user_id = ?");
    $stmtTeam->execute([$_SESSION['user_id']]);
    $team = $stmtTeam->fetch();
    
    if (!$team) {
        http_response_code(403);
        echo json_encode(['error' => 'Tài khoản chưa được gán vào đội cứu hộ']);
        exit;
    }
    
    $team_id = $team['team_id'];
    
    // 2. Lấy danh sách nhiệm vụ (Không gọi cột number_of_people nữa)
    $sql = "
        SELECT 
            m.mission_id, m.status AS mission_status, m.assigned_at, m.note_from_team,
            rq.request_id, rq.citizen_name, rq.phone, rq.address_note, rq.latitude, rq.longitude, 
            rq.severity, rq.description
        FROM missions m
        JOIN rescue_requests rq ON m.request_id = rq.request_id
        WHERE m.team_id = ? AND m.status != 'Hoàn thành'
        ORDER BY 
            CASE m.status 
                WHEN 'Đang cứu hộ' THEN 1 
                WHEN 'Đang di chuyển' THEN 2
                ELSE 3
            END,
            m.assigned_at ASC
    ";
    
    $stmtMissions = $pdo->prepare($sql);
    $stmtMissions->execute([$team_id]);
    $missions = $stmtMissions->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($missions);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>