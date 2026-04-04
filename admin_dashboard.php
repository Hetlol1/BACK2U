<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit();
}

$uid = $_SESSION['user_id'];
$pRes = mysqli_query($conn, "SELECT profile_photo, name FROM users WHERE id='$uid'");
$pRow = mysqli_fetch_assoc($pRes);
$encodedName    = urlencode($pRow['name'] ?? 'Admin');
$fallbackAvatar = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";
$avatarSrc      = !empty($pRow['profile_photo']) ? htmlspecialchars($pRow['profile_photo']) : $fallbackAvatar;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — BACK2U</title>
<style>
:root{--bg:#f0f2f5;--surface:#ffffff;--surface2:#f8f9fa;--border:#e0e0e0;--text:#1a1a1a;--text-muted:#666;--header-bg:#003366;--accent:#003366;--accent-hover:#004080;--shadow:rgba(0,0,0,0.07);--input-bg:#fff;--input-border:#ddd;}
[data-theme="dark"]{--bg:#0f1117;--surface:#1e2130;--surface2:#262a3a;--border:#2e3348;--text:#e8eaf6;--text-muted:#9fa8c0;--header-bg:#0d1b3e;--accent:#4a80d4;--accent-hover:#5a90e4;--shadow:rgba(0,0,0,0.4);--input-bg:#262a3a;--input-border:#2e3348;}
*{margin:0;padding:0;box-sizing:border-box;transition:background-color 0.3s,color 0.2s,border-color 0.2s;}
body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);}
header{background:var(--header-bg);color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,0.2);}
.header-left{display:flex;align-items:center;gap:12px;}
.header-right{display:flex;align-items:center;gap:15px;}
.admin-chip{background:#ffc107;color:#333;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:bold;}
.theme-toggle{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:white;width:38px;height:38px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;}
.theme-toggle:hover{background:rgba(255,255,255,0.25);}
.avatar-link{display:flex;align-items:center;gap:8px;color:white;text-decoration:none;}
.avatar-link img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);}
.container{padding:20px;max-width:1200px;margin:auto;}
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:15px;margin-bottom:25px;}
.stat-card{background:var(--surface);border-radius:10px;padding:20px;text-align:center;box-shadow:0 2px 8px var(--shadow);border:1px solid var(--border);}
.stat-num{font-size:2rem;font-weight:bold;}
.stat-label{font-size:0.8rem;color:var(--text-muted);margin-top:4px;text-transform:uppercase;letter-spacing:0.5px;}
.stat-card.lost .stat-num{color:#c62828;} .stat-card.found .stat-num{color:#2e7d32;} .stat-card.claimed .stat-num{color:#1565c0;} .stat-card.pending .stat-num{color:#f57f17;} .stat-card.users .stat-num{color:#6a1b9a;}
[data-theme="dark"] .stat-card.lost .stat-num{color:#ef9a9a;} [data-theme="dark"] .stat-card.found .stat-num{color:#a5d6a7;} [data-theme="dark"] .stat-card.claimed .stat-num{color:#90caf9;} [data-theme="dark"] .stat-card.pending .stat-num{color:#ffe082;} [data-theme="dark"] .stat-card.users .stat-num{color:#ce93d8;}
.panel{background:var(--surface);border-radius:10px;box-shadow:0 2px 8px var(--shadow);margin-bottom:25px;overflow:hidden;border:1px solid var(--border);}
.panel-header{background:var(--header-bg);color:white;padding:14px 20px;font-weight:bold;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
.panel-body{padding:20px;}
.btn{background:var(--accent);color:white;border:none;padding:10px 20px;cursor:pointer;border-radius:5px;font-size:0.9rem;margin:3px;text-decoration:none;display:inline-block;}
.btn:hover{background:var(--accent-hover);}
.btn-danger{background:#dc3545;} .btn-danger:hover{background:#c82333;}
.btn-warning{background:#fd7e14;} .btn-warning:hover{background:#e36b00;}
.btn-success{background:#28a745;} .btn-success:hover{background:#218838;}
.btn-sm{padding:6px 14px;font-size:0.82rem;}
.btn-disabled{background:#aaa;cursor:not-allowed;opacity:0.6;} .btn-disabled:hover{background:#aaa;}
.search-row{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;}
.search-input{padding:10px;border:1px solid var(--input-border);border-radius:5px;font-size:0.95rem;flex:1;min-width:200px;background:var(--input-bg);color:var(--text);}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{text-align:left;padding:10px 12px;background:var(--surface2);color:var(--text-muted);font-size:0.82rem;text-transform:uppercase;letter-spacing:0.4px;border-bottom:2px solid var(--border);}
.data-table td{padding:11px 12px;border-bottom:1px solid var(--border);font-size:0.9rem;vertical-align:middle;color:var(--text);}
.data-table tr:hover td{background:var(--surface2);}
.item-thumb{width:44px;height:44px;object-fit:cover;border-radius:6px;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:0.75rem;font-weight:bold;}
.status-lost{background:#ffebee;color:#c62828;} .status-found{background:#e8f5e9;color:#2e7d32;} .status-claimed{background:#e3f2fd;color:#1565c0;} .status-pending{background:#fff8e1;color:#f57f17;} .status-registered{background:#f3e5f5;color:#6a1b9a;}
[data-theme="dark"] .status-lost{background:#3b1018;color:#ef9a9a;} [data-theme="dark"] .status-found{background:#1a3a2a;color:#a5d6a7;} [data-theme="dark"] .status-claimed{background:#0d2a4a;color:#90caf9;} [data-theme="dark"] .status-pending{background:#3b2e00;color:#ffe082;} [data-theme="dark"] .status-registered{background:#2a1040;color:#ce93d8;}
.role-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:0.75rem;font-weight:bold;background:#e8eaf6;color:#283593;}
.role-admin{background:#fce4ec;color:#880e4f;}
[data-theme="dark"] .role-badge{background:#1a237e;color:#90caf9;} [data-theme="dark"] .role-admin{background:#3b0a1e;color:#f48fb1;}
.you-tag{display:inline-block;margin-left:6px;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:bold;background:#fff3e0;color:#e65100;}
[data-theme="dark"] .you-tag{background:#3b2000;color:#ffcc80;}
.approved-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:0.75rem;font-weight:bold;background:#e8f5e9;color:#2e7d32;}
.pending-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:0.75rem;font-weight:bold;background:#fff8e1;color:#f57f17;}
[data-theme="dark"] .approved-badge{background:#1a3a2a;color:#a5d6a7;} [data-theme="dark"] .pending-badge{background:#3b2e00;color:#ffe082;}
.domain-add-row{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;}
.domain-add-row input{flex:1;min-width:160px;}
.empty-row td{text-align:center;color:var(--text-muted);padding:30px;}
@media(max-width:700px){.stats-row{grid-template-columns:repeat(2,1fr);}.panel-header{flex-direction:column;align-items:flex-start;}}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="nmims-university-logo.png" alt="NMIMS" style="height:45px;object-fit:contain;filter:brightness(0) invert(1);">
    <h3>Back2U</h3>
    <span class="admin-chip">🔑 Admin</span>
  </div>
  <div class="header-right">
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">🌙</button>
    <a href="profile.php" class="avatar-link" title="View Profile">
      <img src="<?php echo $avatarSrc; ?>" alt="Avatar"
           onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>';">
      <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
    </a>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
  </div>
</header>

<div class="container">
  <h2 style="margin-bottom:20px;">Admin Dashboard</h2>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card lost"><div class="stat-num" id="statLost">—</div><div class="stat-label">Lost</div></div>
    <div class="stat-card found"><div class="stat-num" id="statFound">—</div><div class="stat-label">Found</div></div>
    <div class="stat-card claimed"><div class="stat-num" id="statClaimed">—</div><div class="stat-label">Claimed</div></div>
    <div class="stat-card pending"><div class="stat-num" id="statPending">—</div><div class="stat-label">Pending</div></div>
    <div class="stat-card users"><div class="stat-num" id="statUsers">—</div><div class="stat-label">Total Users</div></div>
  </div>

  <!-- Item Management -->
  <div class="panel">
    <div class="panel-header">
      <span>🗂 All Items</span>
      <div>
        <button class="btn btn-warning" onclick="cleanupDuplicates()">Clean Duplicates</button>
        <button class="btn btn-danger"  onclick="deleteAllItems()">Delete All Items</button>
      </div>
    </div>
    <div class="panel-body">
      <div class="search-row">
        <input type="text" id="itemSearch" class="search-input" placeholder="Search items..." oninput="filterItems()">
        <select id="itemStatusFilter" class="search-input" style="flex:0;min-width:140px;" onchange="filterItems()">
          <option value="">All Status</option>
          <option value="lost">Lost</option>
          <option value="registered">Registered</option>
          <option value="pending">Pending</option>
          <option value="claimed">Claimed</option>
        </select>
      </div>
      <table class="data-table">
        <thead><tr><th>Photo</th><th>Title</th><th>Description</th><th>Status</th><th>Action</th></tr></thead>
        <tbody id="itemTableBody"><tr class="empty-row"><td colspan="5">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- User Management -->
  <div class="panel">
    <div class="panel-header"><span>👥 All Users</span></div>
    <div class="panel-body">
      <div class="search-row">
        <input type="text" id="userSearch" class="search-input" placeholder="Search by name or email..." oninput="filterUsers()">
        <select id="userRoleFilter" class="search-input" style="flex:0;min-width:140px;" onchange="filterUsers()">
          <option value="">All Roles</option>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody id="userTableBody"><tr class="empty-row"><td colspan="5">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- Domain Management -->
  <div class="panel">
    <div class="panel-header"><span>🌐 Allowed College Domains</span></div>
    <div class="panel-body">
      <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:14px;">
        Domains matching <strong>*.ac.in</strong>, <strong>*.edu.in</strong>, <strong>*.edu</strong>, <strong>*.res.in</strong> are auto-approved.<br>
        Add custom domains below for colleges that don't match these patterns.
      </p>
      <div class="domain-add-row">
        <input type="text" id="newDomain"  class="search-input" placeholder="Domain e.g. somaiya.edu">
        <input type="text" id="newCollege" class="search-input" placeholder="College name e.g. Somaiya Vidyavihar">
        <button class="btn btn-success" onclick="addDomain()">+ Add Domain</button>
      </div>
      <table class="data-table">
        <thead><tr><th>Domain</th><th>College</th><th>Status</th><th>Added On</th><th>Action</th></tr></thead>
        <tbody id="domainTableBody"><tr class="empty-row"><td colspan="5">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const CURRENT_ADMIN_ID = <?php echo (int)$uid; ?>;
let allItems = [], allUsers = [];

/* ── Theme ── */
function toggleTheme(){
    const html=document.documentElement;
    const isDark=html.getAttribute('data-theme')==='dark';
    html.setAttribute('data-theme',isDark?'light':'dark');
    document.getElementById('themeToggle').textContent=isDark?'🌙':'☀️';
    localStorage.setItem('theme',isDark?'light':'dark');
}
(function(){
    const s=localStorage.getItem('theme')||'light';
    document.documentElement.setAttribute('data-theme',s);
    document.getElementById('themeToggle').textContent=s==='dark'?'☀️':'🌙';
})();

/* ── Load data ── */
fetch('get_items.php')
    .then(r=>r.json()).then(data=>{allItems=data;updateStats(data);renderItems(data);})
    .catch(()=>{document.getElementById('itemTableBody').innerHTML='<tr class="empty-row"><td colspan="5">Failed to load items.</td></tr>';});

fetch('get_users.php')
    .then(r=>r.json()).then(data=>{allUsers=data;document.getElementById('statUsers').textContent=data.length;renderUsers(data);})
    .catch(()=>{document.getElementById('userTableBody').innerHTML='<tr class="empty-row"><td colspan="5">Failed to load users.</td></tr>';});

loadDomains();

function updateStats(items){
    document.getElementById('statLost').textContent    = items.filter(i=>i.status==='lost').length;
    document.getElementById('statFound').textContent   = items.filter(i=>i.status==='found').length;
    document.getElementById('statClaimed').textContent = items.filter(i=>i.status==='claimed').length;
    document.getElementById('statPending').textContent = items.filter(i=>i.status==='pending').length;
}

/* ── Items ── */
function renderItems(items){
    const tbody=document.getElementById('itemTableBody');
    if(!items.length){tbody.innerHTML='<tr class="empty-row"><td colspan="5">No items found.</td></tr>';return;}
    tbody.innerHTML=items.map(item=>{
        const img=item.image_path?item.image_path.replace(/^\/+/,''):'';
        const fallback=`https://ui-avatars.com/api/?name=${encodeURIComponent(item.title||'Item')}&background=e9ecef&color=003366&size=100`;
        return `<tr>
            <td><img src="${img||fallback}" class="item-thumb" onerror="this.onerror=null;this.src='${fallback}'"></td>
            <td><strong>${escHtml(item.title)}</strong></td>
            <td style="color:var(--text-muted);">${escHtml((item.description||'').substring(0,60))}${item.description&&item.description.length>60?'...':''}</td>
            <td><span class="status-badge status-${escHtml(item.status)}">${escHtml(item.status.toUpperCase())}</span></td>
            <td><button class="btn btn-danger btn-sm" onclick="deleteItem(${item.id})">Delete</button></td>
        </tr>`;
    }).join('');
}

function filterItems(){
    const s=document.getElementById('itemSearch').value.toLowerCase();
    const f=document.getElementById('itemStatusFilter').value;
    renderItems(allItems.filter(i=>(!s||(i.title||'').toLowerCase().includes(s))&&(!f||i.status===f)));
}

/* ── Users ── */
function renderUsers(users){
    const tbody=document.getElementById('userTableBody');
    if(!users.length){tbody.innerHTML='<tr class="empty-row"><td colspan="5">No users found.</td></tr>';return;}
    tbody.innerHTML=users.map(u=>{
        let joined='—';
        if(u.created_at){const d=new Date(u.created_at);joined=d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});}
        const isSelf=parseInt(u.id)===CURRENT_ADMIN_ID;
        const actionBtn=isSelf
            ?`<button class="btn btn-sm btn-disabled" disabled title="Cannot delete your own account">Delete</button>`
            :`<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">Delete</button>`;
        const youTag=isSelf?`<span class="you-tag">You</span>`:'';
        return `<tr>
            <td><strong>${escHtml(u.name)}</strong>${youTag}</td>
            <td>${escHtml(u.email)}</td>
            <td><span class="role-badge ${u.role==='admin'?'role-admin':''}">${escHtml(u.role)}</span></td>
            <td style="font-size:0.85rem;">${joined}</td>
            <td>${actionBtn}</td>
        </tr>`;
    }).join('');
}

function filterUsers(){
    const s=document.getElementById('userSearch').value.toLowerCase();
    const r=document.getElementById('userRoleFilter').value;
    renderUsers(allUsers.filter(u=>(!s||(u.name||'').toLowerCase().includes(s)||(u.email||'').toLowerCase().includes(s))&&(!r||u.role===r)));
}

/* ── Domains ── */
function loadDomains(){
    fetch('get_domains.php')
        .then(r=>r.json())
        .then(data=>renderDomains(data))
        .catch(()=>{document.getElementById('domainTableBody').innerHTML='<tr class="empty-row"><td colspan="5">Failed to load domains.</td></tr>';});
}

function renderDomains(domains){
    const tbody=document.getElementById('domainTableBody');
    if(!domains.length){tbody.innerHTML='<tr class="empty-row"><td colspan="5">No custom domains yet. Auto-patterns (*.ac.in, *.edu.in, *.edu) are always active.</td></tr>';return;}
    tbody.innerHTML=domains.map(d=>`<tr>
        <td><strong>@${escHtml(d.domain)}</strong></td>
        <td>${escHtml(d.college||'—')}</td>
        <td>${d.approved==1
            ?'<span class="approved-badge">✅ Approved</span>'
            :'<span class="pending-badge">⏳ Pending</span>'}</td>
        <td style="font-size:0.85rem;">${d.created_at?new Date(d.created_at).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}):'—'}</td>
        <td>
            ${d.approved==0?`<button class="btn btn-success btn-sm" onclick="approveDomain(${d.id})">Approve</button>`:''}
            <button class="btn btn-danger btn-sm" onclick="deleteDomain(${d.id})">Remove</button>
        </td>
    </tr>`).join('');
}

function addDomain(){
    const domain  = document.getElementById('newDomain').value.trim().toLowerCase().replace(/^@/,'');
    const college = document.getElementById('newCollege').value.trim();
    if(!domain){alert('Please enter a domain.');return;}
    const fd=new FormData();
    fd.append('action','add');fd.append('domain',domain);fd.append('college',college);
    fetch('manage_domains.php',{method:'POST',body:fd})
        .then(r=>r.json()).then(d=>{
            alert(d.message);
            if(d.status==='success'){
                document.getElementById('newDomain').value='';
                document.getElementById('newCollege').value='';
                loadDomains();
            }
        });
}

function approveDomain(id){
    const fd=new FormData();fd.append('action','approve');fd.append('id',id);
    fetch('manage_domains.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{alert(d.message);loadDomains();});
}

function deleteDomain(id){
    if(!confirm('Remove this domain? Users from this domain will no longer be able to register.'))return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    fetch('manage_domains.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{alert(d.message);loadDomains();});
}

/* ── Items & users actions ── */
function deleteItem(id){if(!confirm("Delete this item?"))return;fetch('delete_item.php',{method:'POST',body:new URLSearchParams({item_id:id})}).then(r=>r.json()).then(d=>{alert(d.message);location.reload();});}
function deleteUser(id){if(id===CURRENT_ADMIN_ID){alert("You cannot delete your own account.");return;}if(!confirm("Delete this user? This cannot be undone."))return;fetch('delete_user.php',{method:'POST',body:new URLSearchParams({user_id:id})}).then(r=>r.json()).then(d=>{alert(d.message);location.reload();});}
function deleteAllItems(){if(!confirm("Delete ALL items? This cannot be undone."))return;fetch('delete_all_items.php',{method:'POST'}).then(r=>r.json()).then(d=>{alert(d.message);location.reload();});}
function cleanupDuplicates(){fetch('cleanup_duplicates.php',{method:'POST'}).then(r=>r.json()).then(d=>{alert(d.message);location.reload();});}
function escHtml(str){return String(str||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
</body>
</html>
