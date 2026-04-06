<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$uid      = $_SESSION['user_id'];
$reportId = intval($_GET['report_id'] ?? 0);

$rStmt = mysqli_prepare($conn, "SELECT * FROM found_reports WHERE id = ? AND finder_id = ?");
mysqli_stmt_bind_param($rStmt, 'ii', $reportId, $uid);
mysqli_stmt_execute($rStmt);
$report = mysqli_fetch_assoc(mysqli_stmt_get_result($rStmt));

if (!$report) {
    header("Location: dashboard.php");
    exit();
}

// Filter lost items by college domain
$domain = $_SESSION['domain'] ?? '';
if (empty($domain)) {
    $r = mysqli_query($conn, "SELECT email FROM users WHERE id=$uid");
    $row = mysqli_fetch_assoc($r);
    if ($row && strpos($row['email'], '@') !== false) {
        $domain = strtolower(explode('@', $row['email'])[1]);
        $_SESSION['domain'] = $domain;
    }
}

$lostItems = [];
$lStmt = mysqli_prepare($conn,
    "SELECT i.*, u.name AS owner_name
     FROM items i
     LEFT JOIN users u ON i.owner_id = u.id
     WHERE i.status = 'lost' AND i.college_domain = ?
     ORDER BY i.updated_at DESC, i.id DESC");
mysqli_stmt_bind_param($lStmt, 's', $domain);
mysqli_stmt_execute($lStmt);
$lRes = mysqli_stmt_get_result($lStmt);
while ($row = mysqli_fetch_assoc($lRes)) {
    $lostItems[] = $row;
}

$pRes        = mysqli_query($conn, "SELECT name, profile_photo FROM users WHERE id='$uid'");
$pRow        = mysqli_fetch_assoc($pRes);
$encodedName = urlencode($pRow['name'] ?? 'User');
$fallbackAvatar = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";
$avatarSrc   = !empty($pRow['profile_photo']) ? htmlspecialchars($pRow['profile_photo']) : $fallbackAvatar;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Potential Matches — Back2U</title>
<script>
(function(){
    var t=localStorage.getItem('theme')||'light';
    document.documentElement.setAttribute('data-theme',t);
})();
</script>
<style>
:root{
    --bg:#f0f2f5;--surface:#ffffff;--surface2:#f8f9fa;--border:#e0e0e0;
    --text:#1a1a1a;--text-muted:#666;--text-faint:#999;
    --header-bg:#003366;--header-text:#ffffff;
    --accent:#003366;--accent-hover:#004080;--shadow:rgba(0,0,0,0.08);
    --chat-bg:#e5ddd5;--msg-sent:#dcf8c6;--msg-recv:#ffffff;
    --input-bg:#ffffff;--input-border:#dddddd;
    --ai-card-bg:#f8f9ff;--ai-card-border:#e0e7ff;
    --info-bg:#fff8e1;--info-border:#ffe082;--info-text:#795548;
}
[data-theme="dark"]{
    --bg:#0f1117;--surface:#1e2130;--surface2:#262a3a;--border:#2e3348;
    --text:#e8eaf6;--text-muted:#9fa8c0;--text-faint:#5c6480;
    --header-bg:#0d1b3e;--header-text:#e8eaf6;
    --accent:#4a80d4;--accent-hover:#5a90e4;--shadow:rgba(0,0,0,0.4);
    --chat-bg:#151820;--msg-sent:#1a3a2a;--msg-recv:#1e2130;
    --input-bg:#262a3a;--input-border:#2e3348;
    --ai-card-bg:#1a1f35;--ai-card-border:#2e3a6e;
    --info-bg:#3b2e00;--info-border:#856404;--info-text:#ffe082;
}
*{margin:0;padding:0;box-sizing:border-box;transition:background-color 0.3s,color 0.2s,border-color 0.2s;}
body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;}
header{background:var(--header-bg);color:var(--header-text);padding:15px 20px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px var(--shadow);}
.header-left{display:flex;align-items:center;gap:12px;}
.header-right{display:flex;align-items:center;gap:15px;}
.avatar-link{display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--header-text);}
.avatar-link img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);}
.theme-toggle{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:var(--header-text);width:38px;height:38px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;}
.theme-toggle:hover{background:rgba(255,255,255,0.25);}

.page{max-width:1100px;margin:0 auto;padding:30px 20px 60px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--accent);text-decoration:none;font-size:0.9rem;margin-bottom:20px;}
.back-link:hover{text-decoration:underline;}

.report-summary{background:var(--surface);border-radius:10px;box-shadow:0 2px 8px var(--shadow);padding:20px;margin-bottom:28px;display:flex;gap:20px;align-items:flex-start;border:1px solid var(--border);}
.report-summary img{width:100px;height:100px;object-fit:cover;border-radius:8px;flex-shrink:0;}
.report-summary .info h3{color:var(--accent);margin-bottom:6px;}
.report-summary .info p{color:var(--text-muted);font-size:0.9rem;line-height:1.5;}
.report-summary .info .meta{font-size:0.78rem;color:var(--text-faint);margin-top:6px;}

.section-title{color:var(--accent);font-size:1.2rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.count-badge{background:var(--accent);color:white;font-size:0.75rem;padding:2px 10px;border-radius:12px;}

.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;}
.card{background:var(--surface);border-radius:8px;overflow:hidden;box-shadow:0 2px 8px var(--shadow);transition:0.2s;border:1px solid var(--border);}
.card:hover{transform:translateY(-3px);box-shadow:0 6px 16px var(--shadow);}
.card img{width:100%;height:170px;object-fit:cover;}
.card-body{padding:14px;}
.card-title{font-size:1rem;font-weight:bold;margin-bottom:5px;color:var(--text);}
.card-desc{font-size:0.85rem;color:var(--text-muted);margin-bottom:10px;line-height:1.4;}
.card-owner{font-size:0.78rem;color:var(--text-faint);margin-bottom:10px;}

.btn{background:var(--accent);color:white;border:none;padding:9px 18px;cursor:pointer;border-radius:5px;font-size:0.85rem;text-decoration:none;display:inline-block;transition:0.2s;}
.btn:hover{background:var(--accent-hover);}
.btn-outline{background:var(--surface);color:var(--accent);border:1px solid var(--accent);}
.btn-outline:hover{background:var(--accent);color:white;}
.status-badge{display:inline-block;padding:3px 9px;border-radius:12px;font-size:0.72rem;font-weight:bold;margin-bottom:8px;background:#ffebee;color:#c62828;}
[data-theme="dark"] .status-badge{background:#3b1018;color:#ef9a9a;}

.empty-state{text-align:center;padding:60px 20px;color:var(--text-faint);background:var(--surface);border-radius:10px;border:1px solid var(--border);}
.empty-state .icon{font-size:3rem;margin-bottom:10px;}
.info-box{background:var(--info-bg);border:1px solid var(--info-border);border-radius:6px;padding:12px 16px;margin-bottom:24px;font-size:0.88rem;color:var(--info-text);line-height:1.5;}
.section-divider{border:none;border-top:2px solid var(--border);margin:36px 0;}

.ai-section{background:var(--surface);border-radius:10px;box-shadow:0 2px 8px var(--shadow);padding:24px;margin-bottom:28px;border:1px solid var(--border);}
.ai-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;}
.ai-header h3{color:var(--accent);font-size:1.1rem;margin:0;}
.ai-run-btn{background:var(--accent);color:white;border:none;padding:10px 22px;border-radius:5px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:0.2s;}
.ai-run-btn:hover:not(:disabled){background:var(--accent-hover);}
.ai-run-btn:disabled{background:#aaa;cursor:not-allowed;}
.ai-info{background:#e8f4fd;border:1px solid #bee3f8;border-radius:6px;padding:10px 14px;font-size:0.84rem;color:#1a6e9e;margin-bottom:16px;line-height:1.5;}
[data-theme="dark"] .ai-info{background:#0d2a4a;border-color:#1e4a7a;color:#90caf9;}

.ai-match-card{background:var(--ai-card-bg);border:1px solid var(--ai-card-border);border-radius:8px;padding:16px;margin-bottom:12px;display:flex;gap:16px;align-items:flex-start;transition:0.2s;}
.ai-match-card:hover{box-shadow:0 3px 10px var(--shadow);}
.ai-match-card img{width:90px;height:90px;object-fit:cover;border-radius:6px;flex-shrink:0;}
.ai-match-info{flex:1;}
.ai-match-title{font-size:1rem;font-weight:bold;color:var(--text);margin-bottom:4px;}
.ai-match-desc{font-size:0.85rem;color:var(--text-muted);margin-bottom:6px;line-height:1.4;}
.ai-match-reason{font-size:0.82rem;color:var(--text-faint);font-style:italic;margin-bottom:10px;}
.confidence-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:0.73rem;font-weight:bold;margin-bottom:8px;}
.confidence-high{background:#d4edda;color:#155724;}
.confidence-medium{background:#fff3cd;color:#856404;}
.confidence-low{background:#f8d7da;color:#721c24;}
[data-theme="dark"] .confidence-high{background:#1a3a2a;color:#a5d6a7;}
[data-theme="dark"] .confidence-medium{background:#3b2e00;color:#ffe082;}
[data-theme="dark"] .confidence-low{background:#3b1018;color:#ef9a9a;}

/* Chat modal */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;}
.modal-box{background:var(--surface);width:450px;height:520px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;border-radius:10px;overflow:hidden;border:1px solid var(--border);}
.modal-header{background:var(--header-bg);color:white;padding:15px;display:flex;justify-content:space-between;align-items:center;}
.modal-close{background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;line-height:1;}
.modal-body{flex:1;padding:15px;overflow-y:auto;background:var(--chat-bg);}
.modal-footer{padding:12px;border-top:1px solid var(--border);display:flex;gap:8px;background:var(--surface);}
.modal-footer input{flex:1;padding:10px;border:1px solid var(--input-border);border-radius:20px;font-size:0.9rem;outline:none;background:var(--input-bg);color:var(--text);}
.modal-footer input:focus{border-color:var(--accent);}
.modal-footer button{background:var(--accent);color:white;border:none;border-radius:20px;padding:10px 18px;cursor:pointer;}
.modal-footer button:hover{background:var(--accent-hover);}
.msg{padding:9px 13px;margin:6px 0;border-radius:15px;max-width:75%;word-wrap:break-word;font-size:0.9rem;}
.msg.sent{background:var(--msg-sent);margin-left:auto;border-bottom-right-radius:4px;color:var(--text);}
.msg.recv{background:var(--msg-recv);border-bottom-left-radius:4px;color:var(--text);box-shadow:0 1px 2px var(--shadow);}
.msg-sender{font-size:0.72rem;font-weight:bold;color:var(--accent);margin-bottom:3px;}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="back2u-logo.png" alt="NMIMS" style="height:42px;object-fit:contain;">
    <h3>Back2U</h3>
  </div>
  <div class="header-right">
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">🌙</button>
    <a href="profile.php" class="avatar-link">
      <img src="<?php echo $avatarSrc; ?>" alt="Profile"
           onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>'">
    </a>
    <a href="logout.php" style="color:var(--header-text);text-decoration:none;">Logout</a>
  </div>
</header>

<div class="page">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  <h2 style="color:var(--accent);margin-bottom:6px;">Your Found Report</h2>
  <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:20px;">Your report has been saved. Use AI matching or browse all lost items below.</p>

  <!-- Report summary -->
  <div class="report-summary">
    <?php
    $imgSrc      = !empty($report['image_path']) ? htmlspecialchars($report['image_path']) : '';
    $imgFallback = "https://ui-avatars.com/api/?name=Found+Item&background=e9ecef&color=003366&size=200";
    ?>
    <img src="<?php echo $imgSrc ?: $imgFallback; ?>"
         onerror="this.onerror=null;this.src='<?php echo $imgFallback; ?>'">
    <div class="info">
      <h3>What you found:</h3>
      <p><?php echo htmlspecialchars($report['description']); ?></p>
      <p class="meta">Submitted: <?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></p>
    </div>
  </div>

  <!-- AI Matching -->
  <div class="ai-section">
    <div class="ai-header">
      <h3>🤖 AI Image Matching</h3>
      <button class="ai-run-btn" id="aiBtn" onclick="runAIMatch()">✨ Run AI Match</button>
    </div>
    <div class="ai-info">
      ℹ️ Click <strong>Run AI Match</strong> to let Gemini AI compare your found item's photo against lost items from your college. Results are ranked <strong>High</strong>, <strong>Medium</strong>, or <strong>Low</strong> confidence.
    </div>
    <div id="aiResults"></div>
  </div>

  <hr class="section-divider">

  <div class="info-box">
    🔎 <strong>Browse manually:</strong> Check the lost items below. If you spot a match, click <strong>"Chat with Owner"</strong> to contact them directly.
  </div>

  <div class="section-title">
    All Lost Items — <?php echo htmlspecialchars($domain); ?>
    <span class="count-badge"><?php echo count($lostItems); ?></span>
  </div>

  <?php if (empty($lostItems)): ?>
    <div class="empty-state">
      <div class="icon">🎉</div>
      <p>No items are currently reported as lost from your college.</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($lostItems as $item):
        $imgPath   = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : '';
        $fallback  = "https://ui-avatars.com/api/?name=" . urlencode($item['title']) . "&background=e9ecef&color=003366&size=200";
        $safeTitle = htmlspecialchars($item['title'] ?? 'Item');
      ?>
      <div class="card">
        <img src="<?php echo $imgPath ?: $fallback; ?>"
             onerror="this.onerror=null;this.src='<?php echo $fallback; ?>'">
        <div class="card-body">
          <span class="status-badge">LOST</span>
          <div class="card-title"><?php echo $safeTitle; ?></div>
          <div class="card-desc"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 80)) . (strlen($item['description'] ?? '') > 80 ? '…' : ''); ?></div>
          <div class="card-owner">👤 <?php echo htmlspecialchars($item['owner_name'] ?? 'Unknown'); ?></div>
          <button class="btn btn-outline" style="margin-right:6px;font-size:0.8rem;"
                  onclick="openChat(<?php echo $item['id']; ?>, '<?php echo addslashes($safeTitle); ?>')">View</button>
          <button class="btn" style="font-size:0.8rem;"
                  onclick="openChat(<?php echo $item['id']; ?>, '<?php echo addslashes($safeTitle); ?>')">💬 Chat with Owner</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Chat Modal -->
<div class="modal-overlay" id="chatModal">
  <div class="modal-box">
    <div class="modal-header">
      <span id="chatTitle" style="font-weight:bold;">Chat</span>
      <button class="modal-close" onclick="closeChat()">&times;</button>
    </div>
    <div class="modal-body" id="chatBody"></div>
    <div class="modal-footer">
      <input type="text" id="msgInput" placeholder="Type a message..."
             onkeydown="if(event.key==='Enter')sendMsg()">
      <button onclick="sendMsg()">Send</button>
    </div>
  </div>
</div>

<script>
const userId   = <?php echo json_encode((int)$_SESSION['user_id']); ?>;
const reportId = <?php echo json_encode($reportId); ?>;
let currentItemId = null;
let pollInterval  = null;

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
    document.getElementById('themeToggle').textContent=s==='dark'?'☀️':'🌙';
})();

/* ── AI Match ── */
function runAIMatch(){
    const btn=document.getElementById('aiBtn');
    const res=document.getElementById('aiResults');
    btn.disabled=true;
    btn.textContent='⏳ Analysing...';
    res.innerHTML='<p style="color:var(--text-muted);font-style:italic;padding:10px 0;">Sending image to Gemini AI — this may take 5–10 seconds...</p>';
    fetch(`gemini_match.php?report_id=${reportId}`)
        .then(r=>r.json())
        .then(data=>{
            btn.disabled=false;
            btn.textContent='🔄 Re-run AI Match';
            if(data.error){res.innerHTML=`<div style="color:#c62828;background:#ffebee;padding:12px;border-radius:6px;">❌ Error: ${escHtml(data.error)}</div>`;return;}
            if(!data.matches||data.matches.length===0){res.innerHTML='<p style="color:var(--text-muted);padding:10px 0;">No confident matches found. Try browsing manually below.</p>';return;}
            res.innerHTML=data.matches.map(m=>{
                const confClass='confidence-'+m.confidence;
                const confLabel=m.confidence.charAt(0).toUpperCase()+m.confidence.slice(1)+' Match';
                const imgPath=m.image_path?m.image_path.replace(/^\/+/,''):'';
                const fallback=`https://ui-avatars.com/api/?name=${encodeURIComponent(m.title)}&background=e9ecef&color=003366&size=200`;
                return `<div class="ai-match-card">
                    <img src="${imgPath||fallback}" onerror="this.onerror=null;this.src='${fallback}'">
                    <div class="ai-match-info">
                        <span class="confidence-badge ${confClass}">${confLabel}</span>
                        <div class="ai-match-title">${escHtml(m.title)}</div>
                        <div class="ai-match-desc">${escHtml(m.description)}</div>
                        <div class="ai-match-reason">🤖 ${escHtml(m.reason)}</div>
                        <button class="btn" style="font-size:0.82rem;" onclick="openChat(${m.item_id},'${escHtml(m.title).replace(/'/g,"\\'")}')">💬 Chat with Owner</button>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(err=>{
            btn.disabled=false;
            btn.textContent='🔄 Re-run AI Match';
            res.innerHTML=`<div style="color:#c62828;background:#ffebee;padding:12px;border-radius:6px;">❌ Network error: ${escHtml(String(err))}</div>`;
        });
}

/* ── Chat ── */
function openChat(id,title){
    currentItemId=id;
    document.getElementById('chatModal').style.display='block';
    document.getElementById('chatTitle').innerText='💬 '+title;
    document.getElementById('chatBody').innerHTML='<p style="text-align:center;color:var(--text-faint);padding:20px;">Loading...</p>';
    loadMsgs();
    clearInterval(pollInterval);
    pollInterval=setInterval(loadMsgs,4000);
}

function closeChat(){
    document.getElementById('chatModal').style.display='none';
    clearInterval(pollInterval);
    currentItemId=null;
}

function loadMsgs(){
    if(!currentItemId)return;
    fetch('get_messages.php?item_id='+currentItemId)
        .then(r=>r.json())
        .then(msgs=>{
            const body=document.getElementById('chatBody');
            if(!msgs.length){body.innerHTML='<p style="text-align:center;color:var(--text-faint);padding:20px;">No messages yet. Say hello! 👋</p>';return;}
            const prev=body.querySelectorAll('.msg').length;
            if(prev!==msgs.length){
                body.innerHTML=msgs.map(m=>{
                    const mine=parseInt(m.sender_id)===userId;
                    return `<div class="msg ${mine?'sent':'recv'}">
                        ${!mine?`<div class="msg-sender">${escHtml(m.sender_name||'User')}</div>`:''}
                        ${escHtml(m.message)}
                    </div>`;
                }).join('');
                body.scrollTop=body.scrollHeight;
            }
        });
}

function sendMsg(){
    const msg=document.getElementById('msgInput').value.trim();
    if(!msg||!currentItemId)return;
    document.getElementById('msgInput').value='';
    const fd=new FormData();
    fd.append('item_id',currentItemId);
    fd.append('message',msg);
    fetch('send_message.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{if(d.status==='success')loadMsgs();});
}

function escHtml(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
</script>
</body>
</html>
