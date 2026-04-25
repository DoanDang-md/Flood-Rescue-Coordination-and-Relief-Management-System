<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Kiểm tra quyền Dispatcher
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') {
    echo json_encode(["success" => false, "message" => "Từ chối truy cập!"]);
    exit;
}

// Lấy dữ liệu từ Frontend gửi lên (dạng JSON)
$data = json_decode(file_get_contents("php://input"), true);
$reqId = $data['request_id'] ?? null;
$teamId = $data['team_id'] ?? null;
$dispatcherId = $_SESSION['user_id'] ?? 2; // Lấy ID của Dispatcher đang đăng nhập (mặc định là 2 nếu chưa có session)

if (!$reqId || !$teamId) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin Yêu cầu hoặc Đội cứu hộ."]);
    exit;
}

try {
    // BẮT ĐẦU TRANSACTION (Bảo vệ tính toàn vẹn dữ liệu)
    $pdo->beginTransaction();

    // 1. Tạo lệnh điều động vào bảng missions
    $sql1 = "INSERT INTO missions (request_id, team_id, dispatcher_id, status) 
             VALUES (:req_id, :team_id, :disp_id, 'Đang di chuyển')";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([':req_id' => $reqId, ':team_id' => $teamId, ':disp_id' => $dispatcherId]);

    // 2. Cập nhật trạng thái Yêu cầu thành "Đang điều phối"
    $sql2 = "UPDATE rescue_requests SET status = 'Đang điều phối' WHERE request_id = :req_id";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([':req_id' => $reqId]);

    // 3. Cập nhật trạng thái Đội cứu hộ thành "Busy" (Bận)
    $sql3 = "UPDATE rescue_teams SET status = 'Busy' WHERE team_id = :team_id";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([':team_id' => $teamId]);

    // CHỐT TRANSACTION (Lưu tất cả xuống DB)
    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Điều phối thành công!"]);

} catch (PDOException $e) {
    // Nếu có lỗi, HỦY TOÀN BỘ (Rollback)
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>