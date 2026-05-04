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

