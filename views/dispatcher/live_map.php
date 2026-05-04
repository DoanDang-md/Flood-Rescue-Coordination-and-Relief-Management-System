<?php
session_start();
if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['Admin', 'Dispatcher'])) {
    header("Location: ../../index.html"); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bản đồ Điều phối Trực tiếp (LIVE)</title>

    <!-- Styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        .map-card { border-radius: 8px; overflow: hidden; }
        #map { height: 550px; width: 100%; z-index: 1; }
        
        /* Layout tùy chỉnh cho danh sách / tab */
        .req-scroll { height: 550px; overflow-y: auto; }
        .req-scroll::-webkit-scrollbar { width: 5px; }
        .req-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
        
        .req-item, .team-item { border-left: 4px solid transparent; padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: .2s; }
        .req-item:hover { background: #f8f9fc; }
        .req-item.selected { background: #eaf1fb; }
        .req-item.b-danger { border-left-color: #e74a3b; }
        .req-item.b-warning { border-left-color: #f6c23e; }
        
        .team-avatar { width: 40px; height: 40px; border-radius: 50%; background: #4e73df; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
        .team-name-txt { font-weight: bold; color: #2c3e50; }

        /* Detail box hiện đè lên map */
        #detail-box { position: absolute; bottom: 20px; left: 20px; right: 20px; background: rgba(255,255,255,0.95); backdrop-filter: blur(5px); border-radius: 10px; padding: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 1000; display: none; }
        #detail-box.show { display: block; }
        
        .live-dot { width: 10px; height: 10px; border-radius: 50%; background: #1cc88a; display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.8)} }
    </style>
</head>

<body id="page-top">
<div id="wrapper">
    <?php include 'layouts/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'layouts/topbar.php'; ?>

            <div class="container-fluid mt-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-satellite-dish mr-2 text-danger"></i>RADAR ĐIỀU PHỐI <span class="live-dot ml-2"></span>
                    </h1>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadAllData()">
                        <i class="fas fa-sync-alt mr-1"></i>Làm mới bản đồ
                    </button>
                </div>

                <div class="row">
                    <!-- CỘT BẢN ĐỒ -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow map-card position-relative">
                            <div id="map"></div>
                            
                            <!-- Box Chi tiết 1 Ca cứu hộ (Hiển thị khi click marker) -->
                            <div id="detail-box">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold text-danger mb-0"><i class="fas fa-bullseye mr-2"></i>MỤC TIÊU ĐANG CHỌN</h6>
                                    <button class="btn btn-sm btn-light" onclick="closeDetail()"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="row" id="detail-body">
                                    <!-- JS sẽ đẩy nội dung vào đây -->
                                </div>
                                <div class="mt-3 border-top pt-2 text-right">
                                    <button class="btn btn-primary shadow-sm" onclick="openAssignTab()"><i class="fas fa-paper-plane mr-2"></i>Chọn đội & Phát lệnh</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CỘT DANH SÁCH / ĐIỀU PHỐI -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs nav-justified" id="sideTab">
                                    <li class="nav-item">
                                        <a class="nav-link active font-weight-bold text-danger" data-toggle="tab" href="#tab-requests">
                                            <i class="fas fa-list-alt mr-1"></i>YÊU CẦU MỚI
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link font-weight-bold text-success" data-toggle="tab" href="#tab-teams">
                                            <i class="fas fa-users mr-1"></i>ĐỘI CỨU HỘ
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body p-0">
                                <div class="tab-content">
                                    <!-- TAB YÊU CẦU -->
                                    <div class="tab-pane fade show active req-scroll" id="tab-requests">
                                        <div id="request-list"></div>
                                        <div id="req-empty" class="text-center text-muted py-5" style="display:none;">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i><br>Bình yên.<br>Không có ca khẩn cấp nào.
                                        </div>
                                    </div>
                                    <!-- TAB ĐỘI CỨU HỘ -->
                                    <div class="tab-pane fade req-scroll" id="tab-teams">
                                        <div class="p-2 bg-light text-center small font-weight-bold text-muted border-bottom">
                                            Chọn 1 Yêu cầu bên Tab kia, sau đó chọn Đội khả dụng ở đây để Điều phối.
                                        </div>
                                        <div id="team-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include 'layouts/footer.php'; ?>
    </div>
</div>


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const API_BASE = '../../api';
    let map, allRequests=[], allTeams=[], markers={}, selectedId=null;

    // Khởi tạo bản đồ
    map = L.map('map').setView([16.047, 108.206], 6);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    function makeIcon(color, sel) {
        const c = { red: '#e74a3b', yellow: '#f6c23e' }[color] || '#f6c23e';
        const sz = sel ? 34 : 26;
        return L.divIcon({
            className: '',
            html: `<div style="width:${sz}px;height:${sz}px;background:${c};border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 0 10px rgba(0,0,0,0.5);"></div>`,
            iconSize: [sz, sz], iconAnchor: [sz/2, sz]
        });
    }

    // Tải dữ liệu
    async function loadAllData() {
        try {
            const [reqRes, teamRes] = await Promise.all([
                fetch(`${API_BASE}/dispatcher/get_requests.php?status=Mới`).then(r => r.json()),
                fetch(`${API_BASE}/dispatcher/get_suggested_teams.php`).then(r => r.json())
            ]);
            allRequests = reqRes.success ? reqRes.data : [];
            allTeams = teamRes.success ? teamRes.data : [];
            
            renderMapAndRequests();
            renderTeams();
        } catch(e) { console.error("Lỗi lấy dữ liệu", e); }
    }

    // Vẽ lên Bản đồ & Danh sách
    function renderMapAndRequests() {
        // Clear cũ
        Object.values(markers).forEach(m => map.removeLayer(m)); markers = {};
        const list = document.getElementById('request-list'); list.innerHTML = '';
        
        if (allRequests.length === 0) {
            document.getElementById('req-empty').style.display = 'block'; return;
        }
        document.getElementById('req-empty').style.display = 'none';

        allRequests.forEach(r => {
            // Render List
            const d = document.createElement('div');
            d.className = `req-item ${r.marker_color === 'red' ? 'b-danger' : 'b-warning'} ${r.request_id === selectedId ? 'selected' : ''}`;
            d.innerHTML = `
                <div class="d-flex justify-content-between font-weight-bold text-dark"><span>${r.citizen_name}</span> <span>${r.phone}</span></div>
                <div class="small text-muted mt-1"><i class="fas fa-map-marker-alt text-danger"></i> ${r.address_note}</div>
            `;
            d.onclick = () => selectRequest(r.request_id);
            list.appendChild(d);

            // Render Map
            if (r.latitude && r.longitude) {
                const m = L.marker([r.latitude, r.longitude], {icon: makeIcon(r.marker_color, r.request_id === selectedId)})
                           .addTo(map).bindPopup(`<b>${r.citizen_name}</b><br>${r.phone}`);
                m.on('click', () => selectRequest(r.request_id));
                markers[r.request_id] = m;
            }
        });
    }

    function renderTeams() {
        const list = document.getElementById('team-list'); list.innerHTML = '';
        allTeams.forEach(t => {
            const isBusy = t.status === 'Busy';
            const btnHtml = isBusy 
                ? `<button class="btn btn-sm btn-secondary" disabled>Đang bận</button>` 
                : `<button class="btn btn-sm btn-outline-success font-weight-bold" onclick="assignMission(${t.team_id})">Điều động</button>`;
            
            list.innerHTML += `
                <div class="team-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="team-avatar">${t.team_name.charAt(0)}</div>
                        <div style="margin-left:10px;">
                            <div class="team-name-txt">${t.team_name}</div>
                            <div class="small text-muted">Đang xử lý: ${t.current_active} ca</div>
                        </div>
                    </div>
                    ${btnHtml}
                </div>
            `;
        });
    }

    function selectRequest(id) {
        selectedId = id;
        renderMapAndRequests(); // Render lại để áp style 'selected'
        const req = allRequests.find(r => r.request_id === id);
        
        if (req.latitude) map.flyTo([req.latitude, req.longitude], 15);

        // Hiện Detail Box
        document.getElementById('detail-body').innerHTML = `
            <div class="col-6">
                <small class="text-muted">Nạn nhân:</small><br><b>${req.citizen_name}</b>
            </div>
            <div class="col-6">
                <small class="text-muted">Điện thoại:</small><br><b>${req.phone}</b>
            </div>
            <div class="col-12 mt-2">
                <small class="text-muted">Địa điểm:</small><br><span class="text-dark">${req.address_note}</span>
            </div>
        `;
        document.getElementById('detail-box').classList.add('show');
    }

    function closeDetail() {
        selectedId = null;
        document.getElementById('detail-box').classList.remove('show');
        renderMapAndRequests();
    }

    function openAssignTab() {
        $('#sideTab a[href="#tab-teams"]').tab('show');
    }

    function assignMission(teamId) {
        if (!selectedId) {
            Swal.fire('Lỗi', 'Vui lòng chọn 1 nạn nhân trên bản đồ trước!', 'warning'); return;
        }
        
        Swal.fire({
            title: 'Xác nhận Điều Động?', text: "Lực lượng sẽ lập tức xuất phát!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#e74a3b', confirmButtonText: 'Phát Lệnh!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${API_BASE}/dispatcher/assign_mission.php`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: selectedId, team_id: teamId })
                }).then(r => r.json()).then(data => {
                    if(data.success) {
                        Swal.fire('Thành công', data.message, 'success');
                        closeDetail();
                        $('#sideTab a[href="#tab-requests"]').tab('show');
                        loadAllData();
                    } else Swal.fire('Lỗi', data.message, 'error');
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => { 
        loadAllData(); 
        setInterval(loadAllData, 10000); // Live map update mỗi 10s
    });
</script>
</body>
</html>