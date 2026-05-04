<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Truy cập bị từ chối!"]);
    exit;
}

try {
    // 1. LẤY CÁC CHỈ SỐ TỔNG QUAN (Stats)
    // Đếm số ca 'Mới'
    $stmtNew = $pdo->query("SELECT COUNT(*) FROM rescue_requests WHERE status = 'Mới'");
    $newRequests = $stmtNew->fetchColumn();

    // Đếm số Đội đang trực chiến (Available hoặc Busy, không tính Offline)
    $stmtTeams = $pdo->query("SELECT COUNT(*) FROM rescue_teams WHERE status != 'Offline'");
    $activeTeams = $stmtTeams->fetchColumn();

    // Đếm số ca 'Hoàn thành'
    $stmtCompleted = $pdo->query("SELECT COUNT(*) FROM rescue_requests WHERE status = 'Hoàn thành'");
    $completedCases = $stmtCompleted->fetchColumn();

    // 2. LẤY DỮ LIỆU BIỂU ĐỒ TRÒN (Pie Chart - Tỉ lệ Trạng thái)
    $stmtPie = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM rescue_requests 
        GROUP BY status
    ");
    $pieDataRaw = $stmtPie->fetchAll(PDO::FETCH_ASSOC);
    
    $pieChartData = [
        "pending" => 0, // Gồm 'Mới', 'Đang điều phối'
        "rescuing" => 0, // Gồm 'Đang cứu hộ'
        "done" => 0      // Gồm 'Hoàn thành'
    ];
    
    foreach ($pieDataRaw as $row) {
        if ($row['status'] === 'Mới' || $row['status'] === 'Đang điều phối') {
            $pieChartData['pending'] += $row['count'];
        } elseif ($row['status'] === 'Đang cứu hộ') {
            $pieChartData['rescuing'] += $row['count'];
        } elseif ($row['status'] === 'Hoàn thành') {
            $pieChartData['done'] += $row['count'];
        }
    }

    // 3. LẤY DỮ LIỆU BIỂU ĐỒ CỘT (Bar Chart - 7 ngày qua)
    // Lấy số lượng ca theo từng ngày trong 7 ngày gần nhất
    $stmtBar = $pdo->query("
        SELECT DATE(created_at) as request_date, COUNT(*) as total
        FROM rescue_requests
        WHERE created_at >= DATE(NOW()) - INTERVAL 6 DAY
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $barDataRaw = $stmtBar->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn bị mảng 7 ngày mặc định (để nếu ngày nào không có ca nào thì số lượng là 0)
    $barLabels = [];
    $barValues = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayOfWeek = date('d/m', strtotime($date)); // Hiển thị định dạng ngày/tháng cho đẹp
        
        $barLabels[] = $dayOfWeek;
        $barValues[$date] = 0; // Mặc định là 0
    }

    // Đắp dữ liệu thật từ DB vào mảng
    foreach ($barDataRaw as $row) {
        if (isset($barValues[$row['request_date']])) {
            $barValues[$row['request_date']] = $row['total'];
        }
    }

    // 4. TRẢ VỀ KẾT QUẢ JSON
    echo json_encode([
        "status" => "success",
        "stats" => [
            "new_requests" => (int)$newRequests,
            "active_teams" => (int)$activeTeams,
            "completed_cases" => (int)$completedCases
        ],
        "charts" => [
            "pie" => [
                $pieChartData['pending'], 
                $pieChartData['rescuing'], 
                $pieChartData['done']
            ],
            "bar" => [
                "labels" => $barLabels,
                "data" => array_values($barValues)
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Lỗi truy vấn: " . $e->getMessage()]);
}
?>