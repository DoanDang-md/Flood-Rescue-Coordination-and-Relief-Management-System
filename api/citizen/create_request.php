<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citizen_name = trim($_POST['citizen_name']);
    $phone = trim($_POST['phone']);
    $address_note = trim($_POST['address_note']);
    $severity = $_POST['severity'];
    $description = trim($_POST['description']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_requests 
            (citizen_name, phone, address_note, latitude, longitude, severity, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$citizen_name, $phone, $address_note, $latitude, $longitude, $severity, $description]);

        // Gửi thành công -> Điều hướng sang trang Tra cứu
        echo "<script>
                alert('GỬI YÊU CẦU THÀNH CÔNG! Đội điều phối đã nhận được tín hiệu. Bạn sẽ được chuyển sang trang theo dõi trạng thái.');
                window.location.href = '../../views/citizen/track_request.html';
              </script>";
        exit;

    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
} else {
    header("Location: ../index.html");
    exit;
}
?>