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
$dispatcherId = $_SESSION['user_id'] ?? 2; 

if (!$reqId || !$teamId) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin Yêu cầu hoặc Đội cứu hộ."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. LOCK & CHECK YÊU CẦU: Có ai khác vừa điều phối ca này chưa?
    $stmtCheckReq = $pdo->prepare("SELECT status FROM rescue_requests WHERE request_id = :req_id FOR UPDATE");
    $stmtCheckReq->execute([':req_id' => $reqId]);
    $reqStatus = $stmtCheckReq->fetchColumn();

    if ($reqStatus !== 'Mới') {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Ca cứu hộ này đã được điều phối viên khác xử lý!"]);
        exit;
    }

    // 2. LOCK & CHECK ĐỘI CỨU HỘ: Đội này có thực sự đang rảnh không?
    $stmtCheckTeam = $pdo->prepare("SELECT status FROM rescue_teams WHERE team_id = :team_id FOR UPDATE");
    $stmtCheckTeam->execute([':team_id' => $teamId]);
    $teamStatus = $stmtCheckTeam->fetchColumn();

    if ($teamStatus !== 'Available') {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Đội cứu hộ vừa nhận lệnh hoặc đang bận! Vui lòng chọn đội khác."]);
        exit;
    }

    // 3. Tạo lệnh điều động vào bảng missions
    $sql1 = "INSERT INTO missions (request_id, team_id, dispatcher_id, status) VALUES (:req_id, :team_id, :disp_id, 'Đang di chuyển')";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([':req_id' => $reqId, ':team_id' => $teamId, ':disp_id' => $dispatcherId]);

    // 4. Cập nhật trạng thái Yêu cầu & Đội cứu hộ
    $stmt2 = $pdo->prepare("UPDATE rescue_requests SET status = 'Đang điều phối' WHERE request_id = :req_id");
    $stmt2->execute([':req_id' => $reqId]);

    $stmt3 = $pdo->prepare("UPDATE rescue_teams SET status = 'Busy' WHERE team_id = :team_id");
    $stmt3->execute([':team_id' => $teamId]);

    // 5. [THÊM MỚI] Cập nhật khối lượng công việc cho Đội
    $sqlWorkload = "UPDATE team_workload 
                    SET total_assigned = total_assigned + 1, 
                        current_active = current_active + 1,
                        last_assigned_at = CURRENT_TIMESTAMP
                    WHERE team_id = :team_id";
    $stmtWorkload = $pdo->prepare($sqlWorkload);
    $stmtWorkload->execute([':team_id' => $teamId]);

    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Điều phối thành công!"]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Lỗi hệ thống: " . $e->getMessage()]);
}
?>