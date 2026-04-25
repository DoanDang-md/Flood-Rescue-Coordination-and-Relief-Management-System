<?php
session_start();
// Bảo mật: Chỉ Điều phối viên mới được truy cập
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') { 
    header("Location: ../../index.html"); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Bản đồ điểm cứu hộ - Dispatcher</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        /* Ép khung bên phải không được lấn Sidebar */
        #content-wrapper { flex: 1; min-width: 0; position: relative; z-index: 1; }
        
        /* Style cho Bản đồ & Bộ lọc */
        .map-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; }
        .map-title { color: #4e73df; font-weight: 700; font-size: 1.1rem; }
        .chip { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid #dee2e6; background: #fff; cursor: pointer; transition: all .2s; color: #5a5c69;}
        .chip:hover { background: #eaecf4; }
        .chip.act-all { background: #4e73df; color: #fff; border-color: #4e73df; }
        .chip.act-red { background: #e74a3b; color: #fff; border-color: #e74a3b; }
        .chip.act-yellow { background: #f6c23e; color: #fff; border-color: #f6c23e; }
        
        /* Chú thích Bản đồ */
        .map-legend { position: absolute; bottom: 20px; right: 20px; background: white; padding: 12px 16px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; }
        .legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        
        /* Bảng Chi tiết Yêu cầu */
        #detail-card { display: none; border-top: 4px solid #4e73df; border-radius: 8px; }
        .detail-label { font-size: 0.75rem; font-weight: 700; color: #858796; text-transform: uppercase; margin-bottom: 0.25rem; }
        .detail-value { font-size: 1rem; color: #5a5c69; margin-bottom: 1.5rem; }
    </style>
</head>
<body id="page-top">
<div id="wrapper" style="display: flex; align-items: stretch;">
    
    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column bg-light">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>
            
            <div class="container-fluid pb-4">
                
                <div class="card shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header map-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 map-title"><i class="fas fa-map-marked-alt mr-2"></i>Bản đồ điểm cứu hộ</h6>
                        <div class="d-flex gap-2">
                            <button class="chip act-all mr-2" onclick="filterMap('all', this)">Tất cả</button>
                            <button class="chip mr-2" onclick="filterMap('red', this)"><span class="text-danger mr-1">●</span>Khẩn cấp</button>
                            <button class="chip" onclick="filterMap('yellow', this)"><span class="text-warning mr-1">●</span>Bình thường</button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="position:relative;">
                        <div id="map" style="height: 550px; background-color: #1a1a1a;"></div>
                        <div class="map-legend">
                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-2">CHÚ THÍCH</div>
                            <div class="mb-1 text-sm text-gray-700"><span class="legend-dot bg-danger"></span>Critical / High</div>
                            <div class="mb-1 text-sm text-gray-700"><span class="legend-dot bg-warning"></span>Medium / Low</div>
                            <div class="text-sm text-gray-700"><span class="legend-dot bg-success"></span>Hoàn thành</div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm" id="detail-card">
                    <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between border-0">
                        <h6 class="m-0 font-weight-bold text-primary" style="font-size: 1.1rem;">
                            <i class="fas fa-info-circle mr-2"></i>Chi tiết yêu cầu
                        </h6>
                        <button type="button" class="close" onclick="closeDetail()">&times;</button>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row" id="detail-body">
                            </div>
                        <hr class="mt-2 mb-4">
                        <div class="text-left">
                            <button class="btn btn-danger font-weight-bold px-4 py-2" onclick="openAssignFlow()">
                                <i class="fas fa-bolt mr-2"></i>Điều phối đội cứu hộ
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>

<div class="modal fade" id="dispatchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-bolt mr-2"></i>Điều phối Đội cứu hộ</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-warning small mb-3 text-dark">
                    <strong>Đang phân công cho ca:</strong> <span id="modal-req-name" class="font-weight-bold text-danger"></span>
                </div>
                <h6 class="font-weight-bold text-gray-800 mb-3">Danh sách Đội cứu hộ (Ưu tiên Sẵn sàng):</h6>
                <div id="team-list-container" class="list-group shadow-sm">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php include 'layouts/core_scripts.php'; ?>

<script>
    let map;
    let mapMarkers = {}; // Quản lý các điểm ghim trên bản đồ
    let selectedRequestId = null; // Quản lý xem điểm nào đang được click

    // Hàm tạo Icon (Điểm ghim giọt nước)
    function makeIcon(color, isSelected) {
        const c = { 
            red: { f: '#e74a3b', g: 'rgba(231,74,59,.5)' }, 
            yellow: { f: '#f6c23e', g: 'rgba(246,194,62,.5)' }, 
            green: { f: '#1cc88a', g: 'rgba(28,200,138,.5)' } 
        }[color] || { f: '#f6c23e', g: 'rgba(246,194,62,.5)' };
        
        const sz = isSelected ? 34 : 26; // Phóng to nếu được chọn
        const border = isSelected ? '3px solid #fff' : '2px solid rgba(255,255,255,.4)'; // Viền trắng nếu chọn
        
        return L.divIcon({
            className: '',
            html: `<div style="width:${sz}px;height:${sz}px;background:${c.f};border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:${border};box-shadow:0 0 12px ${c.g}; transition: all 0.2s;"></div>`,
            iconSize: [sz, sz], iconAnchor: [sz/2, sz]
        });
    }

    // Khởi tạo bản đồ
    function initMap() {
        map = L.map('map', { center:[10.762622, 106.660172], zoom: 12 });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);
        setTimeout(() => { map.invalidateSize(); }, 500); // Fix lỗi co dãn
    }

    // Vẽ Marker từ DB
    function renderMapMarkers() {
        Object.values(mapMarkers).forEach(m => map.removeLayer(m));
        mapMarkers = {};
        const bounds = [];
        
        const filteredReqs = currentFilter === 'all' ? allRequests : allRequests.filter(r => r.marker_color === currentFilter);

        filteredReqs.forEach(r => {
            if(!r.latitude || !r.longitude) return;
            
            let color = r.marker_color || 'yellow';
            if (r.status === 'Hoàn thành') color = 'green';
            const isSelected = (r.request_id === selectedRequestId);

            // TẠO MARKER (Không có popup dư thừa, chỉ hiện bảng chi tiết bên dưới)
            const m = L.marker([r.latitude, r.longitude], {icon: makeIcon(color, isSelected)}).addTo(map);
            
            // XỬ LÝ CLICK
            m.on('click', () => {
                // Tắt viền marker cũ
                if (selectedRequestId && mapMarkers[selectedRequestId]) {
                    const oldReq = allRequests.find(req => req.request_id === selectedRequestId);
                    if (oldReq) {
                        let oldColor = oldReq.marker_color || 'yellow';
                        if(oldReq.status === 'Hoàn thành') oldColor = 'green';
                        mapMarkers[selectedRequestId].setIcon(makeIcon(oldColor, false));
                    }
                }
                
                // Bật viền marker mới
                selectedRequestId = r.request_id;
                mapMarkers[selectedRequestId].setIcon(makeIcon(color, true));

                showDetail(r);
                map.flyTo([r.latitude, r.longitude], 15, { duration: 1.0 });
            });

            mapMarkers[r.request_id] = m;
            bounds.push([r.latitude, r.longitude]);
        });

        if(bounds.length > 0) map.fitBounds(bounds, {padding: [50, 50], maxZoom: 14});
    }

    // Hiển thị Bảng chi tiết
    function showDetail(r) {
        let dotColor = r.marker_color === 'red' ? '#e74a3b' : (r.marker_color === 'yellow' ? '#f6c23e' : '#1cc88a');
        let statusBadge = r.status === 'Hoàn thành' ? 'success' : 'primary';

        document.getElementById('detail-body').innerHTML = `
            <div class="col-md-6 col-lg-4">
                <div class="detail-label">Người yêu cầu</div>
                <div class="detail-value text-primary font-weight-bold">${esc(r.citizen_name)}</div>
                <div class="detail-label">Điện thoại</div>
                <div class="detail-value">${esc(r.phone)}</div>
                <div class="detail-label">Địa chỉ</div>
                <div class="detail-value">${esc(r.address_note)}</div>
                <div class="detail-label">Mô tả</div>
                <div class="detail-value">${esc(r.description) || '<i>Không có mô tả</i>'}</div>
            </div>
            <div class="col-md-6 col-lg-8">
                <div class="detail-label">Mức độ</div>
                <div class="detail-value font-weight-bold">
                    <span style="color: ${dotColor};">●</span> ${r.severity}
                </div>
                <div class="detail-label">Trạng thái</div>
                <div class="detail-value"><span class="badge badge-${statusBadge} px-2 py-1">${esc(r.status)}</span></div>
                <div class="detail-label">Đội xử lý</div>
                <div class="detail-value text-muted font-italic">
                    ${r.team_name ? `<span class="text-success font-weight-bold" style="font-style:normal;">${esc(r.team_name)}</span>` : 'Chưa phân công'}
                </div>
            </div>
        `;
        $('#detail-card').fadeIn(200);
    }

    // Đóng Bảng chi tiết
    function closeDetail() { 
        $('#detail-card').fadeOut(200); 
        // Tắt viền marker
        if (selectedRequestId && mapMarkers[selectedRequestId]) {
            const req = allRequests.find(r => r.request_id === selectedRequestId);
            if (req) {
                let color = req.marker_color || 'yellow';
                if(req.status === 'Hoàn thành') color = 'green';
                mapMarkers[selectedRequestId].setIcon(makeIcon(color, false));
            }
            selectedRequestId = null;
        }
    }

    // Lọc Bản đồ
    function filterMap(color, btn) {
        currentFilter = color;
        // Reset nút
        document.querySelectorAll('.chip').forEach(b => {
            b.classList.remove('act-all', 'act-red', 'act-yellow');
            b.classList.add('bg-white');
        });
        btn.classList.remove('bg-white');
        btn.classList.add(`act-${color}`);
        
        closeDetail(); 
        renderMapMarkers();
    }

    // Mở Modal Điều phối
    // Mở Modal Điều phối
    function openAssignFlow() {
        // Kiểm tra xem đã chọn ca nào trên bản đồ chưa
        if (!selectedRequestId) {
            alert("Vui lòng click chọn một ca cứu hộ trên bản đồ trước!");
            return;
        }
        
        // Lấy thông tin ca đang chọn
        const req = allRequests.find(r => r.request_id === selectedRequestId);
        if (!req) return;

        // Đổ tên lên Modal
        document.getElementById('modal-req-name').textContent = req.citizen_name + " - " + req.address_note;
        
        const container = document.getElementById('team-list-container');
        container.innerHTML = '';
        
        // Render danh sách đội
        if (!allTeams || allTeams.length === 0) {
            container.innerHTML = '<div class="p-3 text-center text-muted">Không có đội cứu hộ nào! Vui lòng kiểm tra lại Database.</div>';
        } else {
            allTeams.forEach(t => {
                const isBusy = t.status !== 'Available';
                const badge = isBusy ? '<span class="badge badge-warning">Đang bận</span>' : '<span class="badge badge-success">Sẵn sàng</span>';
                
                container.innerHTML += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 font-weight-bold text-primary"><i class="fas fa-truck-pickup mr-2"></i>${esc(t.team_name)}</h6>
                            <small class="text-muted">Nhân sự: ${t.member_count} | Hoàn thành: ${t.completed_cases}</small>
                        </div>
                        <div class="text-right">
                            <div class="mb-2">${badge}</div>
                            <button class="btn btn-sm btn-${isBusy ? 'secondary' : 'danger'}" 
                                    onclick="assignTeam(${req.request_id}, ${t.team_id})" ${isBusy ? 'disabled' : ''}>
                                Giao nhiệm vụ
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        // Bật Modal lên
        $('#dispatchModal').modal('show');
    }

    // Giao nhiệm vụ (Chuẩn bị nối API)
    // Giao nhiệm vụ bằng cách gọi API POST
async function assignTeam(reqId, teamId) {
    if (!confirm("Bạn chắc chắn muốn điều động Đội này?")) return;

    // 1. Bắt lấy nút bấm một cách an toàn để tránh lỗi sập Javascript
    let btn = null;
    let originalText = "Giao nhiệm vụ";
    
    // Nếu bắt được sự kiện click
    if (window.event) {
        // currentTarget đảm bảo lấy đúng thẻ <button>, dù có click trúng cái icon <i> bên trong
        btn = window.event.currentTarget; 
        if (btn) {
            originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang giao...';
            btn.disabled = true;
        }
    }

    try {
        // 2. Gọi API
        const res = await fetch(`${API_BASE}/dispatcher/assign_mission.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: reqId,
                team_id: teamId
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            $('#dispatchModal').modal('hide');
            await loadMapPage(); // Tự động load lại bản đồ
            alert("✅ " + data.message);
        } else {
            alert("❌ Lỗi: " + data.message);
            // Phục hồi lại nút nếu bị lỗi
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
    } catch (error) {
        console.error("Lỗi kết nối:", error);
        alert("❌ Mất kết nối đến máy chủ.");
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
}

    // Tải dữ liệu ban đầu
    async function loadMapPage() {
        await fetchCoreData(); 
        renderMapMarkers();    
    }

    // Khởi chạy
    document.addEventListener('DOMContentLoaded', () => { 
        initMap(); 
        loadMapPage(); 
        setInterval(loadMapPage, 10000); // 10s reload DB
    });
</script>
</body>
</html>