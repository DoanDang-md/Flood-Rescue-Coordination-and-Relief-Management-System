<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Cứu hộ Lũ lụt | Đội cứu hộ</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../../assets/css/rescuer.css">
</head>
<body>

<!-- Header -->
<header class="app-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="fas fa-life-ring me-2"></i>CỨU HỘ LŨ LỤT</h3>
                <small><i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Đang hoạt động</small>
            </div>
            <a href="#" onclick="logout()" class="logout-btn" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <!-- Team Info -->
    <div class="team-info">
        <div>
            <i class="fas fa-users me-2" style="color: #2a5298;"></i>
            <span id="team-name">Đội cứu hộ</span>
            <span class="team-badge ms-2" id="team-id-badge">#001</span>
        </div>
        <button class="refresh-btn" onclick="loadMissions()"><i class="fas fa-sync-alt"></i> Làm mới</button>
    </div>

    <!-- Main Content Container (Nơi JS sẽ nhồi thẻ Card vào) -->
    <div id="main-content">
        <div class="loading-container">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
            <p class="mt-3 text-muted">Đang tải danh sách nhiệm vụ...</p>
        </div>
    </div>

    <!-- Auto Refresh Indicator -->
    <div class="refresh-indicator">
        <i class="fas fa-sync-alt fa-spin me-1"></i> Tự động cập nhật sau <span id="countdown">30</span> giây<br>
        <small class="text-muted">Lần cập nhật cuối: <span id="last-update">--:--:--</span></small>
    </div>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>    
<script>
// ==================== GLOBAL VARIABLES ====================
let currentMissions = [];
let autoRefreshTimer = null;
let countdownTimer = null;
let countdownValue = 30;

// Cập nhật đúng đường dẫn thư mục API
const API_BASE = '../../api/rescuer';

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    // Load missions lần đầu (API này sẽ tự động check đăng nhập)
    loadMissions();
    
    // Bắt đầu auto refresh
    startAutoRefresh();
});

function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) {
        fetch('../../api/logout.php')
            .then(() => {
                window.location.href = '../../web_portal.php';
            })
            .catch(() => {
                window.location.href = '../../web_portal.php';
            });
    }
}

// ==================== LOAD MISSIONS ====================
async function loadMissions() {
    const container = document.getElementById('main-content');
    
    try {
        const response = await fetch(`${API_BASE}/get_missions.php`);
        
        // Kiểm tra nếu chưa đăng nhập thì đẩy về trang chủ
        if (response.status === 401 || response.status === 403) {
            window.location.href = '../../web_portal.php';
            return;
        }
        
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        
        const data = await response.json();
        if (data.error) throw new Error(data.error);
        
        currentMissions = data;
        renderMissions(data);
        updateLastUpdateTime();
        resetCountdown();
        
    } catch (error) {
        console.error('Load missions error:', error);
        showError(container, error.message);
    }
}

// ==================== RENDER MISSIONS ====================
function renderMissions(missions) {
    const container = document.getElementById('main-content');
    
    if (!missions || missions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-check-circle text-success"></i>
                <h4>Không có nhiệm vụ đang chờ</h4>
                <p class="text-muted">Đội đang rảnh rỗi. Hãy chờ điều phối tiếp theo!</p>
                <button class="btn btn-outline-primary mt-3 font-weight-bold" onclick="loadMissions()">
                    <i class="fas fa-sync-alt me-2"></i>Làm mới ngay
                </button>
            </div>
        `;
        return;
    }
    
    let html = '';
    missions.forEach(mission => { html += renderMissionCard(mission); });
    container.innerHTML = html;
}

function renderMissionCard(mission) {
    let cardClass = 'mission-card';
    if (mission.mission_status === 'Đang cứu hộ') cardClass += ' rescuing';
    if (mission.severity === 'Critical') cardClass += ' critical pulse';
    
    let statusBadge = '';
    if (mission.mission_status === 'Đang di chuyển') {
        statusBadge = '<span class="status-badge moving"><i class="fas fa-truck me-1"></i>ĐANG DI CHUYỂN</span>';
    } else if (mission.mission_status === 'Đang cứu hộ') {
        statusBadge = '<span class="status-badge rescuing"><i class="fas fa-hands-helping me-1"></i>ĐANG CỨU HỘ</span>';
    }
    
    let severityTag = '';
    if (mission.severity === 'Critical') severityTag = '<span class="severity-critical"><i class="fas fa-exclamation-triangle me-1"></i>KHẨN CẤP</span>';
    else if (mission.severity === 'High') severityTag = '<span class="severity-high"><i class="fas fa-arrow-up me-1"></i>ƯU TIÊN CAO</span>';
    
    let actionButton = '';
    if (mission.mission_status === 'Đang di chuyển') {
        actionButton = `
            <button class="btn-action btn-rescuing shadow-sm" onclick="updateStatus(${mission.mission_id}, 'Đang cứu hộ')">
                <i class="fas fa-map-marker-alt"></i> TỚI HIỆN TRƯỜNG
            </button>
        `;
    } else if (mission.mission_status === 'Đang cứu hộ') {
        actionButton = `
            <button class="btn-action btn-complete shadow-sm" onclick="completeMission(${mission.mission_id})">
                <i class="fas fa-check-circle"></i> BÁO CÁO HOÀN THÀNH
            </button>
        `;
    }
    
    const mapUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${mission.longitude-0.005}%2C${mission.latitude-0.005}%2C${mission.longitude+0.005}%2C${mission.latitude+0.005}&layer=mapnik&marker=${mission.latitude}%2C${mission.longitude}`;
    const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${mission.latitude},${mission.longitude}`;
    
    return `
        <div class="${cardClass}">
            ${severityTag ? `<div class="severity-tag">${severityTag}</div>` : ''}
            ${statusBadge}
            
            <div class="citizen-name">
                <i class="fas fa-user-circle me-1"></i>${escapeHtml(mission.citizen_name)}
            </div>
            
            <div class="info-row"><i class="fas fa-phone-alt"></i><span>${escapeHtml(mission.phone)}</span></div>
            <div class="info-row"><i class="fas fa-map-pin"></i><span>${escapeHtml(mission.address_note)}</span></div>
            
            ${mission.description ? `
            <div class="info-row"><i class="fas fa-info-circle"></i><span>${escapeHtml(mission.description)}</span></div>
            ` : ''}
            
            <div class="map-preview"><iframe src="${mapUrl}" loading="lazy"></iframe></div>
            <a href="${googleMapsUrl}" target="_blank" class="btn-maps"><i class="fas fa-directions text-danger"></i> Dẫn đường Google Maps</a>
            
            ${actionButton}
            
            <div class="text-muted mt-3 text-center" style="font-size: 11px;">
                <i class="far fa-clock me-1"></i>Giao lúc: ${formatDateTime(mission.assigned_at)}
            </div>
        </div>
    `;
}

// ==================== UPDATE STATUS ====================
async function updateStatus(missionId, newStatus) {
    // Hiển thị hộp thoại xác nhận cực đẹp
    const confirmResult = await Swal.fire({
        title: 'Tới hiện trường?',
        text: "Xác nhận đội đã đến nơi và BẮT ĐẦU CỨU HỘ!",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#28a745', // Màu xanh lá
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check"></i> Xác nhận',
        cancelButtonText: 'Chưa tới'
    });

    if (!confirmResult.isConfirmed) return; // Nếu bấm "Chưa tới" thì dừng lại
    
    // Khóa các nút bấm để tránh double-click
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
    
    // Hiện hiệu ứng đang xử lý (Loading)
    Swal.fire({
        title: 'Đang xử lý...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch(`${API_BASE}/update_status.php?mission_id=${missionId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Lỗi cập nhật');
        
        // Báo thành công
        await Swal.fire({
            title: 'Thành công!',
            text: result.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        
        await loadMissions();
        
    } catch (error) {
        Swal.fire('Lỗi rồi!', error.message, 'error');
    } finally {
        buttons.forEach(btn => btn.disabled = false);
    }
}

// ==================== COMPLETE MISSION ====================
async function completeMission(missionId) {
    // Dùng SweetAlert để tạo form nhập Ghi chú thay cho prompt()
    const { value: note, isConfirmed } = await Swal.fire({
        title: 'Báo cáo hoàn thành',
        icon: 'success',
        input: 'textarea',
        inputLabel: 'Tóm tắt tình hình cứu hộ:',
       
        inputAttributes: {
            'aria-label': 'Nhập ghi chú'
        },
        showCancelButton: true,
        confirmButtonColor: '#007bff', // Màu xanh dương
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Gửi báo cáo',
        cancelButtonText: 'Hủy'
    });

    if (!isConfirmed) return; // Nếu bấm Hủy thì dừng
    
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);
    
    Swal.fire({
        title: 'Đang đồng bộ...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    try {
        const response = await fetch(`${API_BASE}/update_status.php?mission_id=${missionId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: 'Hoàn thành',
                note: note // Lấy nội dung ghi chú từ hộp thoại SweetAlert
            })
        });
        
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Lỗi cập nhật');
        
        // Bắn pháo hoa báo thành công ^^
        await Swal.fire({
            title: 'Nhiệm vụ hoàn tất!',
            text: result.message,
            icon: 'success',
            confirmButtonText: 'Tuyệt vời',
            confirmButtonColor: '#28a745'
        });
        
        await loadMissions();
        
    } catch (error) {
        Swal.fire('Lỗi rồi!', error.message, 'error');
    } finally {
        buttons.forEach(btn => btn.disabled = false);
    }
}

// ==================== AUTO REFRESH ====================
function startAutoRefresh() {
    autoRefreshTimer = setInterval(() => { loadMissions(); }, 30000);
    countdownTimer = setInterval(() => {
        countdownValue--;
        document.getElementById('countdown').textContent = countdownValue;
        if (countdownValue <= 0) countdownValue = 30;
    }, 1000);
}

function resetCountdown() {
    countdownValue = 30;
    document.getElementById('countdown').textContent = countdownValue;
}

function updateLastUpdateTime() {
    const now = new Date();
    document.getElementById('last-update').textContent = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ==================== ERROR HANDLING & UTILS ====================
function showError(container, message) {
    container.innerHTML = `
        <div class="error-alert text-center">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <p><strong>Lỗi kết nối!</strong><br>${escapeHtml(message)}</p>
            <button class="btn btn-outline-danger btn-sm mt-2 px-4" onclick="loadMissions()"><i class="fas fa-redo me-2"></i>Thử lại</button>
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return '';
    try {
        const date = new Date(dateTimeStr);
        return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    } catch (e) { return dateTimeStr; }
}

window.addEventListener('beforeunload', function() {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);
});
</script>

</body>
</html>