<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["error" => "Truy cập bị từ chối!"]);
    exit;
}

try {
    // Sửa lỗi sai tên cột của TV6: r.location -> r.address_note, rt.name -> rt.team_name
    $sql = "
        SELECT 
            r.request_id AS id, 
            r.citizen_name, 
            r.address_note AS location, 
            r.status, 
            r.created_at,
            rt.team_name,
            tw.completed_cases AS workload_hours -- Tạm map qua số ca đã xong thay cho số giờ
        FROM rescue_requests r
        LEFT JOIN missions m ON r.request_id = m.request_id
        LEFT JOIN rescue_teams rt ON m.team_id = rt.team_id
        LEFT JOIN team_workload tw ON rt.team_id = tw.team_id
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Lỗi CSDL: " . $e->getMessage()]);
}
?>