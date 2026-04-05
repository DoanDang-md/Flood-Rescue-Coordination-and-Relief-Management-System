-- Tạo Database (Nếu chưa có)
CREATE DATABASE IF NOT EXISTS flood_rescue_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flood_rescue_db;

-- 1. Bảng Vai trò (Roles)
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL 
);

-- 2. Bảng Người dùng hệ thống (Users)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- 3. Bảng Yêu cầu cứu hộ (Rescue Requests)
CREATE TABLE rescue_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    citizen_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address_note TEXT NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    severity ENUM('Critical', 'High', 'Medium', 'Low') NOT NULL,
    description TEXT,
    status ENUM('Mới', 'Đang điều phối', 'Đang cứu hộ', 'Hoàn thành') DEFAULT 'Mới',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Bảng Đội cứu hộ (Rescue Teams)
CREATE TABLE rescue_teams (
    team_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL, -- Liên kết 1-1 với tài khoản đăng nhập
    team_name VARCHAR(100) NOT NULL,
    member_count INT DEFAULT 1,
    equipment TEXT,
    status ENUM('Available', 'Busy', 'Offline') DEFAULT 'Available',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 5. Bảng Tải trọng công việc (Team Workload)
CREATE TABLE team_workload (
    workload_id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT UNIQUE NOT NULL, 
    total_assigned INT DEFAULT 0,
    completed_cases INT DEFAULT 0,
    canceled_cases INT DEFAULT 0,
    current_active INT DEFAULT 0,
    last_assigned_at TIMESTAMP NULL,
    FOREIGN KEY (team_id) REFERENCES rescue_teams(team_id)
);

-- 6. Bảng Nhiệm vụ (Missions)
CREATE TABLE missions (
    mission_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    team_id INT NOT NULL,
    dispatcher_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    status ENUM('Đang di chuyển', 'Đang cứu hộ', 'Hoàn thành') DEFAULT 'Đang di chuyển',
    note_from_team TEXT,
    FOREIGN KEY (request_id) REFERENCES rescue_requests(request_id),
    FOREIGN KEY (team_id) REFERENCES rescue_teams(team_id),
    FOREIGN KEY (dispatcher_id) REFERENCES users(user_id)
);