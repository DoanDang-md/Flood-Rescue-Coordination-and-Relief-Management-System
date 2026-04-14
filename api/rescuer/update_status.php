<?php
/**
 * API: Cập nhật trạng thái nhiệm vụ
 * Method: PUT
 * Input JSON: { "status": "Đang cứu hộ", "note": "...", "people_saved": 5 }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập']);
    exit;
}

// Đọc dữ liệu JSON từ PUT request
$input = json_decode(file_get_contents('php://input'), true);

// Lấy mission_id từ query string
$mission_id = isset($_GET['mission_id']) ? intval($_GET['mission_id']) : 0;
$new_status = $input['status'] ?? '';
$note = $input['note'] ?? '';
$people_saved = $input['people_saved'] ?? null;

// Validate input
if (!$mission_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin bắt buộc']);
    exit;
}

try {
    // Lấy team_id của user hiện tại
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
    
    // Kiểm tra nhiệm vụ có thuộc về đội này không
    $stmt = $conn->prepare("
        SELECT m.status, m.team_id, rq.request_id 
        FROM missions m
        JOIN rescue_requests rq ON m.request_id = rq.request_id
        WHERE m.mission_id = ?
    ");
    $stmt->bind_param("i", $mission_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mission = $result->fetch_assoc();
    
    if (!$mission) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy nhiệm vụ']);
        exit;
    }
    
    if ($mission['team_id'] != $team_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Nhiệm vụ này không được giao cho đội của bạn']);
        exit;
    }
    
    $current_status = $mission['status'];
    
    // Validate logic chuyển trạng thái
    $valid_transitions = [
        'Đang di chuyển' => 'Đang cứu hộ',
        'Đang cứu hộ' => 'Hoàn thành'
    ];
    
    if (!isset($valid_transitions[$current_status]) || $valid_transitions[$current_status] !== $new_status) {
        http_response_code(400);
        echo json_encode(['error' => "Không thể chuyển từ '{$current_status}' sang '{$new_status}'"]);
        exit;
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    if ($new_status === 'Hoàn thành') {
        // Cập nhật missions
        $stmt = $conn->prepare("
            UPDATE missions 
            SET status = ?, completed_at = NOW(), note_from_team = ?
            WHERE mission_id = ?
        ");
        $stmt->bind_param("ssi", $new_status, $note, $mission_id);
        $stmt->execute();
        
        // Cập nhật số người được cứu vào rescue_requests
        if ($people_saved && is_numeric($people_saved)) {
            $stmt = $conn->prepare("
                UPDATE rescue_requests 
                SET number_of_people = ? 
                WHERE request_id = ?
            ");
            $stmt->bind_param("ii", $people_saved, $mission['request_id']);
            $stmt->execute();
        }
        
        // Cập nhật team_workload
        $stmt = $conn->prepare("
            UPDATE team_workload 
            SET completed_cases = completed_cases + 1,
                current_active = GREATEST(current_active - 1, 0)
            WHERE team_id = ?
        ");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        
    } else {
        // Cập nhật trạng thái thông thường
        $stmt = $conn->prepare("
            UPDATE missions 
            SET status = ?, note_from_team = ?
            WHERE mission_id = ?
        ");
        $stmt->bind_param("ssi", $new_status, $note, $mission_id);
        $stmt->execute();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Đã chuyển sang trạng thái: {$new_status}"
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>