<?php
session_start();
//if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Dispatcher') {
//    header("Location: ../../index.html"); exit;
//}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dispatcher Dashboard – Flood Rescue</title>

    <!-- SB Admin 2 + FontAwesome (giống admin) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        /* ── Stats cards ── */
        .disp-stat-card {
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .disp-stat-icon {
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .disp-stat-val { font-size: 26px; font-weight: 700; line-height: 1; }
        .disp-stat-lbl { font-size: 11px; color: #858796; font-weight: 500; margin-top: 2px; }

        /* ── Map ── */
        .map-card { border-radius: 8px; overflow: hidden; }
        #map { height: 460px; width: 100%; }

        /* ── Request list ── */
        .req-scroll { max-height: 460px; overflow-y: auto; }
        .req-scroll::-webkit-scrollbar { width: 4px; }
        .req-scroll::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }

        .req-item {
            border-left: 4px solid transparent;
            padding: 10px 14px;
            cursor: pointer;
            transition: background .15s;
            border-bottom: 1px solid #f0f0f0;
        }
        .req-item:hover    { background: #f8f9fc; }
        .req-item.selected { background: #eaf1fb; }
        .req-item.b-danger  { border-left-color: #e74a3b; }
        .req-item.b-warning { border-left-color: #f6c23e; }
        .req-item.b-success { border-left-color: #1cc88a; }

        .req-name { font-weight: 600; font-size: 13px; color: #2e3a5c; }
        .req-addr { font-size: 11px; color: #858796; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .req-meta { display: flex; align-items: center; gap: 6px; margin-top: 4px; }

        /* ── Filter chips ── */
        .chip {
            padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
            border: 1px solid #dee2e6; background: #fff; cursor: pointer;
            transition: all .15s; color: #858796;
        }
        .chip.act-all    { background: #4e73df; color: #fff; border-color: #4e73df; }
        .chip.act-red    { background: #e74a3b; color: #fff; border-color: #e74a3b; }
        .chip.act-yellow { background: #f6c23e; color: #fff; border-color: #f6c23e; }

        /* ── Team list ── */
        .team-item { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
        .team-avatar { width: 36px; height: 36px; border-radius: 50%; background: #4e73df; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .team-name-txt { font-size: 13px; font-weight: 600; color: #2e3a5c; }
        .team-sub { font-size: 11px; color: #858796; }

        /* ── Map legend ── */
        .map-legend { position: absolute; bottom: 28px; right: 12px; background: rgba(255,255,255,.93); border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 12px; z-index: 999; font-size: 11px; pointer-events: none; }
        .ldot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }

        /* ── Detail box ── */
        #detail-box { background: #f8f9fc; border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; display: none; margin-top: 12px; }
        #detail-box.show { display: block; }

        /* ── Live dot ── */
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #1cc88a; display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.8)} }
    </style>
</head>

<body id="page-top">
<div id="wrapper">

    <!-- SIDEBAR -->
    <?php include 'layouts/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">

        <!-- TOPBAR -->
        <?php include 'layouts/topbar.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="container-fluid">

            <!-- Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-broadcast-tower mr-2 text-danger"></i>Bảng Điều Phối Cứu Hộ
                </h1>
                <div>
                    <span class="mr-3 text-muted" style="font-size:13px;">
                        <span class="live-dot"></span> LIVE
                    </span>
                    <button class="btn btn-sm btn-outline-primary shadow-sm" onclick="loadAll()">
                        <i class="fas fa-sync-alt fa-sm mr-1"></i>Làm mới
                    </button>
                </div>
            </div>

            <!-- STATS -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="disp-stat-card bg-white border-left-primary">
                        <div class="disp-stat-icon bg-primary text-white"><i class="fas fa-list"></i></div>
                        <div><div class="disp-stat-val text-primary" id="stat-total">–</div><div class="disp-stat-lbl">TỔNG YÊU CẦU</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="disp-stat-card bg-white border-left-danger">
                        <div class="disp-stat-icon bg-danger text-white"><i class="fas fa-exclamation-circle"></i></div>
                        <div><div class="disp-stat-val text-danger" id="stat-red">–</div><div class="disp-stat-lbl">KHẨN CẤP (ĐỎ)</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="disp-stat-card bg-white border-left-warning">
                        <div class="disp-stat-icon bg-warning text-white"><i class="fas fa-exclamation-triangle"></i></div>
                        <div><div class="disp-stat-val text-warning" id="stat-yellow">–</div><div class="disp-stat-lbl">BÌNH THƯỜNG (VÀNG)</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="disp-stat-card bg-white border-left-success">
                        <div class="disp-stat-icon bg-success text-white"><i class="fas fa-users"></i></div>
                        <div><div class="disp-stat-val text-success" id="stat-teams">–</div><div class="disp-stat-lbl">ĐỘI SẴN SÀNG</div></div>
                    </div>
                </div>
            </div>

            <!-- MAP + LIST -->
            <div class="row">

                <!-- BẢN ĐỒ col-lg-8 -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow map-card">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-map-marked-alt mr-2"></i>Bản đồ điểm cứu hộ
                            </h6>
                            <div class="d-flex gap-1">
                                <button class="chip act-all mr-1" onclick="filterRequests('all',this)">Tất cả</button>
                                <button class="chip mr-1"         onclick="filterRequests('red',this)">🔴 Khẩn cấp</button>
                                <button class="chip"              onclick="filterRequests('yellow',this)">🟡 Bình thường</button>
                            </div>
                        </div>
                        <div class="card-body p-0" style="position:relative;">
                            <div id="map"></div>
                            <div class="map-legend">
                                <div class="font-weight-bold mb-1" style="font-size:10px;color:#858796;">CHÚ THÍCH</div>
                                <div><span class="ldot" style="background:#e74a3b;"></span>Critical / High</div>
                                <div class="mt-1"><span class="ldot" style="background:#f6c23e;"></span>Medium / Low</div>
                                <div class="mt-1"><span class="ldot" style="background:#1cc88a;"></span>Hoàn thành</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail box -->
                    <div id="detail-box">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="font-weight-bold text-primary mb-0">
                                <i class="fas fa-info-circle mr-1"></i>Chi tiết yêu cầu
                            </h6>
                            <button class="btn btn-sm btn-light" onclick="closeDetail()"><i class="fas fa-times"></i></button>
                        </div>
                        <div id="detail-body" class="row small"></div>
                        <div class="mt-3">
                            <button class="btn btn-danger btn-sm" onclick="openAssignFlow()">
                                <i class="fas fa-bolt mr-1"></i>Điều phối đội cứu hộ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- LIST col-lg-4 -->
                <div class="col-lg-4 mb-4">
                    <ul class="nav nav-tabs" id="sideTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-requests">
                                <i class="fas fa-list-alt mr-1"></i>Yêu cầu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-teams">
                                <i class="fas fa-users mr-1"></i>Đội cứu hộ
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content card shadow" style="border-top:none;border-radius:0 0 8px 8px;">
                        <div class="tab-pane fade show active" id="tab-requests">
                            <div class="req-scroll" id="request-list">
                                <div class="text-center text-muted py-4" id="req-empty" style="display:none;">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>Không có yêu cầu
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-teams">
                            <div class="req-scroll" id="team-list">
                                <div class="text-center text-muted py-4" id="team-empty" style="display:none;">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>Không có dữ liệu
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end row -->
        </div><!-- end container-fluid -->
    </div><!-- end #content -->

    <?php include 'layouts/footer.php'; ?>
    </div><!-- end #content-wrapper -->
</div><!-- end #wrapper -->

<!-- jQuery + Bootstrap (SB Admin 2 cần) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const API_BASE = '../../api';
let map, allRequests=[], allTeams=[], markers={}, selectedId=null, currentFilter='all';

/* ── MAP ── */
function initMap() {
    map = L.map('map', { center:[16.047,108.206], zoom:7 });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution:'© OpenStreetMap © CARTO', maxZoom:19
    }).addTo(map);
}

function makeIcon(color, sel) {
    const c = { red:{f:'#e74a3b',g:'rgba(231,74,59,.5)'}, yellow:{f:'#f6c23e',g:'rgba(246,194,62,.5)'}, green:{f:'#1cc88a',g:'rgba(28,200,138,.5)'} }[color]||{f:'#f6c23e',g:'rgba(246,194,62,.5)'};
    const sz = sel ? 34 : 26;
    return L.divIcon({
        className:'',
        html:`<div style="width:${sz}px;height:${sz}px;background:${c.f};border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:${sel?'3px solid #fff':'2px solid rgba(255,255,255,.4)'};box-shadow:0 0 12px ${c.g};"></div>`,
        iconSize:[sz,sz], iconAnchor:[sz/2,sz], popupAnchor:[0,-sz]
    });
}

/* ── RENDER MARKERS ── */
function renderMarkers(reqs) {
    Object.values(markers).forEach(m=>map.removeLayer(m));
    markers={};
    const bounds=[];
    reqs.forEach(r=>{
        if(!r.latitude||!r.longitude) return;
        const color = r.status==='Hoàn thành'?'green':r.marker_color;
        const m = L.marker([r.latitude,r.longitude],{icon:makeIcon(color,false)})
            .addTo(map).bindPopup(buildPopup(r),{maxWidth:240});
        m.on('click',()=>selectRequest(r.request_id));
        markers[r.request_id]=m;
        bounds.push([r.latitude,r.longitude]);
    });
    if(bounds.length) map.fitBounds(bounds,{padding:[40,40],maxZoom:13});
}
// filtered.forEach(r => {
//             if (!r.latitude || !r.longitude) return;

//             let color = r.marker_color || 'yellow';
//             if (r.status === 'Hoàn thành') color = 'green';

//             const m = L.marker([r.latitude, r.longitude], { icon: makeIcon(color, false) })
//                 .addTo(map)
//                 .bindPopup(`<b>${esc(r.citizen_name)}</b><br>${esc(r.phone)}`);
            
//             m.on('click', () => showRequestDetail(r));
            
//             markers[r.request_id] = m;
//             bounds.push([r.latitude, r.longitude]);
//         });
function buildPopup(r) {
    const sev={Critical:'🔴 Khẩn cấp',High:'🟠 Cao',Medium:'🟡 Trung bình',Low:'🟢 Thấp'};
    return `<div style="font-size:12px;"><b>${esc(r.citizen_name)}</b><br><span style="color:#888">${esc(r.address_note)}</span><br>${sev[r.severity]||r.severity} | <b>${esc(r.status)}</b>${r.team_name?`<br><span style="color:#1cc88a">👥 ${esc(r.team_name)}</span>`:''}</div>`;
}

/* ── RENDER REQUEST LIST ── */
function renderRequestList(reqs) {
    const list=document.getElementById('request-list');
    const empty=document.getElementById('req-empty');
    list.querySelectorAll('.req-item').forEach(e=>e.remove());

    const filtered = currentFilter==='all' ? reqs : reqs.filter(r=>r.marker_color===currentFilter&&r.status!=='Hoàn thành');
    if(!filtered.length){ empty.style.display='block'; return; }
    empty.style.display='none';

    const bmap={red:'b-danger',yellow:'b-warning',green:'b-success'};
    filtered.forEach(r=>{
        const color=r.status==='Hoàn thành'?'green':r.marker_color;
        const d=document.createElement('div');
        d.className=`req-item ${bmap[color]||'b-warning'}${r.request_id===selectedId?' selected':''}`;
        d.dataset.id=r.request_id;
        d.innerHTML=`
            <div class="d-flex align-items-center justify-content-between">
                <div class="req-name">${esc(r.citizen_name)}</div>
                ${sBadge(r.status)}
            </div>
            <div class="req-addr">${esc(r.address_note)}</div>
            <div class="req-meta">${svBadge(r.severity)}<span class="ml-auto" style="font-size:10px;color:#adb5bd;">${fmtTime(r.created_at)}</span></div>
        `;
        d.addEventListener('click',()=>selectRequest(r.request_id));
        list.appendChild(d);
    });
}

/* ── RENDER TEAM LIST ── */
function renderTeamList(teams) {
    const list=document.getElementById('team-list');
    const empty=document.getElementById('team-empty');
    list.querySelectorAll('.team-item').forEach(e=>e.remove());
    if(!teams.length){ empty.style.display='block'; return; }
    empty.style.display='none';
    teams.forEach(t=>{
        const ini=t.team_name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
        const bc={Available:'badge-success',Busy:'badge-warning',Offline:'badge-secondary'}[t.status]||'badge-secondary';
        const d=document.createElement('div');
        d.className='team-item';
        d.innerHTML=`<div class="team-avatar">${ini}</div><div><div class="team-name-txt">${esc(t.team_name)}</div><div class="team-sub"><span class="badge ${bc} text-white mr-1">${esc(t.suggestion_label)}</span>${t.current_active} đang xử lý &bull; ${t.completed_cases} hoàn thành</div></div>`;
        list.appendChild(d);
    });
}

/* ── STATS ── */
function updateStats(reqs,teams) {
    document.getElementById('stat-total').textContent  = reqs.length;
    document.getElementById('stat-red').textContent    = reqs.filter(r=>r.marker_color==='red'&&r.status!=='Hoàn thành').length;
    document.getElementById('stat-yellow').textContent = reqs.filter(r=>r.marker_color==='yellow'&&r.status!=='Hoàn thành').length;
    document.getElementById('stat-teams').textContent  = teams.filter(t=>t.status==='Available').length;
}

/* ── SELECT ── */
function selectRequest(id) {
    selectedId=id;
    const req=allRequests.find(r=>r.request_id==id); if(!req) return;
    document.querySelectorAll('.req-item').forEach(el=>el.classList.toggle('selected',parseInt(el.dataset.id)===id));
    Object.entries(markers).forEach(([rid,m])=>{
        const r=allRequests.find(x=>x.request_id==rid); if(!r) return;
        m.setIcon(makeIcon(r.status==='Hoàn thành'?'green':r.marker_color, parseInt(rid)===id));
    });
    if(req.latitude&&req.longitude){ map.flyTo([req.latitude,req.longitude],14,{duration:1}); markers[id]?.openPopup(); }
    openDetail(req);
}

/* ── DETAIL ── */
function openDetail(r) {
    const sl={Critical:'🔴 Critical',High:'🟠 High',Medium:'🟡 Medium',Low:'🟢 Low'};
    document.getElementById('detail-body').innerHTML=`
        <div class="col-md-6">
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Người yêu cầu</small></p>
            <p class="font-weight-bold text-primary">${esc(r.citizen_name)}</p>
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Điện thoại</small></p>
            <p>${esc(r.phone)}</p>
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Địa chỉ</small></p>
            <p>${esc(r.address_note)}</p>
        </div>
        <div class="col-md-6">
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Mức độ</small></p>
            <p>${sl[r.severity]||r.severity}</p>
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Trạng thái</small></p>
            <p>${sBadge(r.status)}</p>
            <p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Đội xử lý</small></p>
            <p>${r.team_name?`<span class="text-success font-weight-bold">👥 ${esc(r.team_name)}</span>`:'<em class="text-muted">Chưa phân công</em>'}</p>
        </div>
        ${r.description?`<div class="col-12"><p class="mb-1"><small class="text-muted text-uppercase font-weight-bold">Mô tả</small></p><p>${esc(r.description)}</p></div>`:''}
    `;
    document.getElementById('detail-box').classList.add('show');
}

function closeDetail() {
    document.getElementById('detail-box').classList.remove('show');
    selectedId=null;
    document.querySelectorAll('.req-item').forEach(e=>e.classList.remove('selected'));
}

function openAssignFlow() {
    alert('Chức năng điều phối sẽ implement tiếp theo!\n(POST /dispatcher/assign)');
}

/* ── FILTER ── */
function filterRequests(f,btn) {
    currentFilter=f;
    document.querySelectorAll('.chip').forEach(b=>b.className='chip');
    btn.className=`chip act-${f}`;
    renderRequestList(allRequests);
}

/* ── LOAD API ── */
async function loadRequests() {
    try { const j=await(await fetch(`${API_BASE}/dispatcher/get_requests.php`)).json(); allRequests=j.success?j.data:getMockRequests(); }
    catch { allRequests=getMockRequests(); }
}
async function loadTeams() {
    try { const j=await(await fetch(`${API_BASE}/dispatcher/get_suggested_teams.php`)).json(); allTeams=j.success?j.data:getMockTeams(); }
    catch { allTeams=getMockTeams(); }
}
async function loadAll() {
    await Promise.all([loadRequests(),loadTeams()]);
    renderMarkers(allRequests); renderRequestList(allRequests); renderTeamList(allTeams); updateStats(allRequests,allTeams);
}

/* ── MOCK DATA ── */
function getMockRequests(){return[
    {request_id:1,citizen_name:'Nguyễn Văn An',  phone:'0901234567',address_note:'Thôn 3, xã Phú Mỹ, Bình Định',        latitude:13.776,longitude:109.223,severity:'Critical',description:'Cả gia đình mắc kẹt trên mái nhà',status:'Mới',           marker_color:'red',   created_at:'2025-10-15 08:30:00',team_name:null},
    {request_id:2,citizen_name:'Trần Thị Bình',  phone:'0912345678',address_note:'Đường Lê Lợi, P. Hải Châu, Đà Nẵng',  latitude:16.068,longitude:108.212,severity:'High',    description:'Người cao tuổi bị thương',       status:'Đang điều phối',marker_color:'red',   created_at:'2025-10-15 09:00:00',team_name:'Đội Cứu Hộ 01'},
    {request_id:3,citizen_name:'Lê Văn Cường',   phone:'0923456789',address_note:'Xã Điện Hòa, Điện Bàn, Quảng Nam',    latitude:15.884,longitude:108.325,severity:'Medium',  description:'Cần thuyền di chuyển',           status:'Mới',           marker_color:'yellow',created_at:'2025-10-15 09:15:00',team_name:null},
    {request_id:4,citizen_name:'Phạm Thị Dung',  phone:'0934567890',address_note:'Thị trấn Lăng Cô, Huế',               latitude:16.260,longitude:108.073,severity:'Low',     description:'Nhà ngập 50cm',                 status:'Hoàn thành',    marker_color:'yellow',created_at:'2025-10-15 07:00:00',team_name:'Đội Cứu Hộ 02'},
    {request_id:5,citizen_name:'Võ Minh Đức',    phone:'0945678901',address_note:'Xã Hòa Bắc, Hòa Vang, Đà Nẵng',      latitude:16.124,longitude:108.051,severity:'Critical', description:'3 người bị cô lập',             status:'Đang cứu hộ',   marker_color:'red',   created_at:'2025-10-15 08:00:00',team_name:'Đội Cứu Hộ 03'},
];}
function getMockTeams(){return[
    {team_id:1,team_name:'Đội Cứu Hộ 01',member_count:6,status:'Available',current_active:0,completed_cases:12,suggestion_label:'Sẵn sàng'},
    {team_id:2,team_name:'Đội Cứu Hộ 02',member_count:4,status:'Busy',     current_active:2,completed_cases:8, suggestion_label:'Đang bận'},
    {team_id:3,team_name:'Đội Cứu Hộ 03',member_count:5,status:'Busy',     current_active:1,completed_cases:15,suggestion_label:'Đang bận'},
    {team_id:4,team_name:'Đội Cứu Hộ 04',member_count:8,status:'Available',current_active:0,completed_cases:5, suggestion_label:'Sẵn sàng'},
];}

/* ── UTILS ── */
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function fmtTime(dt){if(!dt)return'';const d=new Date(dt);return isNaN(d)?dt:d.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});}
function sBadge(s){const m={'Mới':'primary','Đang điều phối':'warning','Đang cứu hộ':'danger','Hoàn thành':'success'};const c=m[s]||'secondary';return`<span class="badge badge-${c}">${esc(s)}</span>`;}
function svBadge(sv){const m={Critical:'danger',High:'warning',Medium:'info',Low:'success'};return`<span class="badge badge-${m[sv]||'secondary'}" style="font-size:10px">${sv}</span>`;}

/* ── BOOT ── */
document.addEventListener('DOMContentLoaded',()=>{ initMap(); loadAll(); setInterval(loadAll,60000); });
</script>
</body>
</html>