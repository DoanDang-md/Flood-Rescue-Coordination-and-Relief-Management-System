

<script>
const API_BASE = '../../api';
let allRequests=[], allTeams=[], markers={}, selectedId=null, currentFilter='all';

// ── UTILS (Giữ nguyên của bạn) ──
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function fmtTime(dt){if(!dt)return'';const d=new Date(dt);return isNaN(d)?dt:d.toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});}
function sBadge(s){const m={'Mới':'primary','Đang điều phối':'warning','Đang cứu hộ':'danger','Hoàn thành':'success'};const c=m[s]||'secondary';return`<span class="badge badge-${c}">${esc(s)}</span>`;}
function svBadge(sv){const m={Critical:'danger',High:'warning',Medium:'info',Low:'success'};return`<span class="badge badge-${m[sv]||'secondary'}" style="font-size:10px">${sv}</span>`;}

// ── GỌI API THẬT TỪ DATABASE ──
async function fetchCoreData() {
    try { 
        const resReq = await fetch(`${API_BASE}/dispatcher/get_requests.php`);
        const jsonReq = await resReq.json();
        allRequests = jsonReq.success ? jsonReq.data : [];
    } catch { console.log("Lỗi tải Requests"); }

    try { 
        const resTeam = await fetch(`${API_BASE}/dispatcher/get_suggested_teams.php`);
        const jsonTeam = await resTeam.json();
        allTeams = jsonTeam.success ? jsonTeam.data : [];
    } catch { console.log("Lỗi tải Teams"); }
}

// ── RENDER MAP & MARKERS (Code xịn của bạn) ──
function makeIcon(color, sel) {
    const c = { red:{f:'#e74a3b',g:'rgba(231,74,59,.5)'}, yellow:{f:'#f6c23e',g:'rgba(246,194,62,.5)'}, green:{f:'#1cc88a',g:'rgba(28,200,138,.5)'} }[color]||{f:'#f6c23e',g:'rgba(246,194,62,.5)'};
    const sz = sel ? 34 : 26;
    return L.divIcon({
        className:'',
        html:`<div style="width:${sz}px;height:${sz}px;background:${c.f};border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:${sel?'3px solid #fff':'2px solid rgba(255,255,255,.4)'};box-shadow:0 0 12px ${c.g};"></div>`,
        iconSize:[sz,sz], iconAnchor:[sz/2,sz], popupAnchor:[0,-sz]
    });
}

function buildPopup(r) {
    const sev={Critical:'🔴 Khẩn cấp',High:'🟠 Cao',Medium:'🟡 Trung bình',Low:'🟢 Thấp'};
    return `<div style="font-size:12px;"><b>${esc(r.citizen_name)}</b><br><span style="color:#888">${esc(r.address_note)}</span><br>${sev[r.severity]||r.severity} | <b>${esc(r.status)}</b>${r.team_name?`<br><span style="color:#1cc88a">👥 ${esc(r.team_name)}</span>`:''}</div>`;
}
</script>