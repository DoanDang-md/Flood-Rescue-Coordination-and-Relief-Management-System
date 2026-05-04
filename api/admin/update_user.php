<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Admin') {
    header("Location: ../../web_portal.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role_id = $_POST['role_id'];
    $password = $_POST['password'];

    // Lấy thông tin Đội cứu hộ từ Form
    $team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
    $member_count = isset($_POST['member_count']) ? (int)$_POST['member_count'] : 1;
    $equipment = isset($_POST['equipment']) ? trim($_POST['equipment']) : '';

    try {
        $pdo->beginTransaction();

        // 1. Cập nhật bảng Users
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("UPDATE users SET full_name=?, phone=?, role_id=?, password_hash=? WHERE user_id=?");
            $stmtUser->execute([$full_name, $phone, $role_id, $hashed, $user_id]);
        } else {
            $stmtUser = $pdo->prepare("UPDATE users SET full_name=?, phone=?, role_id=? WHERE user_id=?");
            $stmtUser->execute([$full_name, $phone, $role_id, $user_id]);
        }

        // 2. Kiểm tra Role hiện tại
        $stmtRole = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
        $stmtRole->execute([$role_id]);
        $role_name = $stmtRole->fetchColumn();

        if ($role_id == 3) {
            if (empty($team_name)) $team_name = $full_name;

            // Kiểm tra xem user này ĐÃ CÓ hồ sơ trong bảng rescue_teams chưa
            $stmtCheckTeam = $pdo->prepare("SELECT team_id FROM rescue_teams WHERE user_id = ?");
            $stmtCheckTeam->execute([$user_id]);
            $existing_team = $stmtCheckTeam->fetch();

            if ($existing_team) {
                // Nếu ĐÃ CÓ (Update)
                $stmtTeamUpdate = $pdo->prepare("UPDATE rescue_teams SET team_name=?, member_count=?, equipment=? WHERE user_id=?");
                $stmtTeamUpdate->execute([$team_name, $member_count, $equipment, $user_id]);
            } else {
                // Nếu CHƯA CÓ (ví dụ: chuyển từ Admin xuống làm Đội cứu hộ) -> INSERT
                $stmtTeamInsert = $pdo->prepare("INSERT INTO rescue_teams (user_id, team_name, member_count, equipment, status) VALUES (?, ?, ?, ?, 'Available')");
                $stmtTeamInsert->execute([$user_id, $team_name, $member_count, $equipment]);
                $new_team_id = $pdo->lastInsertId();

                // Tạo kèm Workload
                $stmtWorkload = $pdo->prepare("INSERT INTO team_workload (team_id, total_assigned, completed_cases, canceled_cases, current_active) VALUES (?, 0, 0, 0, 0)");
                $stmtWorkload->execute([$new_team_id]);
            }
        }

        $pdo->commit();
        $_SESSION['msg'] = "Cập nhật thành công tài khoản: <strong>$full_name</strong>";
        $_SESSION['msg_type'] = "info";

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { 
            $_SESSION['msg'] = "Lỗi: Số điện thoại này đã được sử dụng cho tài khoản khác!";
        } else {
            $_SESSION['msg'] = "Lỗi hệ thống: " . $e->getMessage();
        }
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: ../../views/admin/manage_users.php");
    exit;
}
?>