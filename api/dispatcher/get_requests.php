<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Cho phép gọi từ cùng server
 
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
    // Lấy filter theo status nếu có 
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
    // Lấy filter theo severity nếu có 
    $severityFilter = isset($_GET['severity']) ? trim($_GET['severity']) : null;
 
    $sql = "
        SELECT 
            r.request_id,
            r.citizen_name,
            r.phone,
            r.address_note,
            r.latitude,
            r.longitude,
            r.severity,
            r.description,
            r.status,
            r.created_at,
            -- Lấy thông tin đội đang xử lý (nếu có)
            t.team_name,
            t.team_id,
            m.mission_id,
            m.status AS mission_status
        FROM rescue_requests r
        LEFT JOIN missions m ON r.request_id = m.request_id 
            AND m.status != 'Hoàn thành'
        LEFT JOIN rescue_teams t ON m.team_id = t.team_id
        WHERE 1=1
    ";
 
    $params = [];
 
    if ($statusFilter) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $statusFilter;
    }
 
    if ($severityFilter) {
        $sql .= " AND r.severity = :severity";
        $params[':severity'] = $severityFilter;
    }
 
    // Sắp xếp: Critical lên trước, sau đó theo thời gian mới nhất
    $sql .= "
        ORDER BY 
            FIELD(r.severity, 'Critical', 'High', 'Medium', 'Low'),
            r.created_at DESC
    ";
 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
 
    // Map severity sang màu cho Leaflet marker
    $severityColorMap = [
        'Critical' => 'red',
        'High'     => 'red',
        'Medium'   => 'yellow',
        'Low'      => 'yellow',
    ];
 
    // Thêm field marker_color vào mỗi request để FE dùng trực tiếp
    foreach ($requests as &$row) {
        $row['marker_color'] = $severityColorMap[$row['severity']] ?? 'yellow';
        // Đảm bảo lat/lng là float hoặc null
        $row['latitude']  = $row['latitude']  !== null ? (float)$row['latitude']  : null;
        $row['longitude'] = $row['longitude'] !== null ? (float)$row['longitude'] : null;
    }
    unset($row); // Giải phóng tham chiếu
 
    echo json_encode([
        "success" => true,
        "total"   => count($requests),
        "data"    => $requests
    ]);
 
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Lỗi CSDL: " . $e->getMessage()
    ]);
}
?>