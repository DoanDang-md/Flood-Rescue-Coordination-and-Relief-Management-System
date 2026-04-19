<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $phone = trim($_GET['phone'] ?? '');

    if (empty($phone)) {
        echo json_encode(["error" => "Vui lòng nhập số điện thoại!"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT request_id, citizen_name, phone, address_note, description, latitude, longitude, status, created_at
            FROM rescue_requests
            WHERE phone = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$phone]);
        
        echo json_encode($stmt->fetchAll());
    } catch (PDOException $e) {
        echo json_encode(["error" => "Lỗi CSDL: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Phương thức không hợp lệ!"]);
}
?>