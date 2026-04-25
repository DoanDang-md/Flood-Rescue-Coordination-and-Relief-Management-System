<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
 
require_once '../config.php';
 
// Bảo mật: Chỉ Dispatcher mới được gọi API này
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Truy cập bị từ chối!"
    ]);
    exit;
}
 
try {
    $sql = "
        SELECT 
            t.team_id,
            t.team_name,
            t.member_count,
            t.equipment,
            t.status,
            u.phone       AS contact_phone,
            u.full_name   AS leader_name,
            -- Workload info
            COALESCE(w.current_active, 0)  AS current_active,
            COALESCE(w.total_assigned, 0)  AS total_assigned,
            COALESCE(w.completed_cases, 0) AS completed_cases,
            w.last_assigned_at,
            -- Score để sort: Available=0 (ưu tiên), Busy=1; rồi theo current_active tăng dần
            CASE t.status 
                WHEN 'Available' THEN 0 
                WHEN 'Busy'      THEN 1 
                ELSE 2 
            END AS status_priority
        FROM rescue_teams t
        INNER JOIN users u ON t.user_id = u.user_id
        LEFT JOIN team_workload w ON t.team_id = w.team_id
        WHERE t.status IN ('Available', 'Busy') -- Bỏ qua đội Offline
        ORDER BY 
            status_priority ASC,
            COALESCE(w.current_active, 0) ASC,
            COALESCE(w.completed_cases, 0) DESC
        LIMIT 5
    ";
 
    $stmt = $pdo->query($sql);
    $teams = $stmt->fetchAll();
 
    echo json_encode([
        "success" => true,
        "total"   => count($teams),
        "data"    => $teams
    ]);
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Lỗi CSDL: " . $e->getMessage()
    ]);
}
?>