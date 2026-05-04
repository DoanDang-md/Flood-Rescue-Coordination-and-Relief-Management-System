<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST'); // Đổi PUT thành POST

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : 0;
$new_status = $input['status'] ?? '';
$note = $input['note'] ?? '';
// Đã xóa dòng lấy $people_saved

if (!$mission_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin bắt buộc']);
    exit;
}

try {
    // 1. Kiểm tra Team ID
    $stmtTeam = $pdo->prepare("SELECT team_id FROM rescue_teams WHERE user_id = ?");
    $stmtTeam->execute([$_SESSION['user_id']]);
    $team = $stmtTeam->fetch();
    
    if (!$team) {
        http_response_code(403); echo json_encode(['error' => 'Tài khoản chưa được gán vào đội cứu hộ']); exit;
    }
    $team_id = $team['team_id'];
    
    // 2. Kiểm tra Nhiệm vụ thuộc về Đội này
    $stmtMission = $pdo->prepare("SELECT status, team_id, request_id FROM missions WHERE mission_id = ?");
    $stmtMission->execute([$mission_id]);
    $mission = $stmtMission->fetch();
    
    if (!$mission) {
        http_response_code(404); echo json_encode(['error' => 'Không tìm thấy nhiệm vụ']); exit;
    }
    if ($mission['team_id'] != $team_id) {
        http_response_code(403); echo json_encode(['error' => 'Nhiệm vụ này không thuộc về đội của bạn']); exit;
    }
    
    $current_status = $mission['status'];
    $valid_transitions = [
        'Đang di chuyển' => 'Đang cứu hộ',
        'Đang cứu hộ' => 'Hoàn thành'
    ];
    
    if (!isset($valid_transitions[$current_status]) || $valid_transitions[$current_status] !== $new_status) {
        http_response_code(400); echo json_encode(['error' => "Không thể chuyển từ '{$current_status}' sang '{$new_status}'"]); exit;
    }
    
    // 3. THỰC THI TRANSACTION ĐỒNG BỘ DỮ LIỆU
    $pdo->beginTransaction();
    
    if ($new_status === 'Hoàn thành') {
        // A. Cập nhật bảng missions
        $stmtUpdateM = $pdo->prepare("UPDATE missions SET status = ?, completed_at = CURRENT_TIMESTAMP, note_from_team = ? WHERE mission_id = ?");
        $stmtUpdateM->execute([$new_status, $note, $mission_id]);
        
        // B. Cập nhật bảng rescue_requests (Chỉ update trạng thái, không update số người)
        $stmtUpdateR = $pdo->prepare("UPDATE rescue_requests SET status = 'Hoàn thành' WHERE request_id = ?");
        $stmtUpdateR->execute([$mission['request_id']]);
        
        // C. Cập nhật bảng team_workload
        $stmtUpdateW = $pdo->prepare("UPDATE team_workload SET completed_cases = completed_cases + 1, current_active = GREATEST(current_active - 1, 0) WHERE team_id = ?");
        $stmtUpdateW->execute([$team_id]);

        // D. Kiểm tra nếu Đội đã xong hết việc thì bật trạng thái Available
        $stmtCheckActive = $pdo->prepare("SELECT current_active FROM team_workload WHERE team_id = ?");
        $stmtCheckActive->execute([$team_id]);
        if ($stmtCheckActive->fetchColumn() == 0) {
            $pdo->prepare("UPDATE rescue_teams SET status = 'Available' WHERE team_id = ?")->execute([$team_id]);
        }
        
    } else {
        // Chỉ cập nhật trạng thái thông thường (Từ Đang di chuyển -> Đang cứu hộ)
        $stmtUpdateM = $pdo->prepare("UPDATE missions SET status = ?, note_from_team = ? WHERE mission_id = ?");
        $stmtUpdateM->execute([$new_status, $note, $mission_id]);

        $stmtUpdateR = $pdo->prepare("UPDATE rescue_requests SET status = 'Đang cứu hộ' WHERE request_id = ?");
        $stmtUpdateR->execute([$mission['request_id']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => "Đã chuyển sang trạng thái: {$new_status}"]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>