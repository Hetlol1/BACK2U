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

$uid          = $_SESSION['user_id'];
$pRes         = mysqli_query($conn, "SELECT name, profile_photo FROM users WHERE id='$uid'");
$pRow         = mysqli_fetch_assoc($pRes);
$encodedName  = urlencode($pRow['name'] ?? 'User');
$avatarSrc    = !empty($pRow['profile_photo']) ? htmlspecialchars($pRow['profile_photo']) : '';
$fallbackAvatar = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";

$error   = '';
$success = '';

/* ── Handle POST ──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';

    /* ── Mode A: Register an Item (Owner) ── */
    if ($mode === 'register') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$title || !$description) {
            $error = 'Please fill in all fields.';
        } elseif (empty($_FILES['image']['name'])) {
            $error = 'Please upload an image.';
        } else {
            $uploadDir = 'uploads/items/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image format.';
            } else {
                $filename   = 'item_' . $uid . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO items (owner_id, title, description, image_path, status) VALUES (?, ?, ?, ?, 'registered')");
                    mysqli_stmt_bind_param($stmt, 'isss', $uid, $title, $description, $targetPath);
                    if (mysqli_stmt_execute($stmt)) {
                        $success = 'Item registered successfully! You can mark it as lost from your My Items page.';
                    } else {
                        $error = 'Database error: ' . mysqli_error($conn);
                    }
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }
    }

    /* ── Mode B: I Found an Item (Finder) ── */
    elseif ($mode === 'found_report') {
        $description = trim($_POST['found_description'] ?? '');

        if (!$description) {
            $error = 'Please enter a description of what you found.';
        } elseif (empty($_FILES['found_image']['name'])) {
            $error = 'Please upload an image of the found item.';
        } else {
            $uploadDir = 'uploads/found/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext     = strtolower(pathinfo($_FILES['found_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Invalid image format.';
            } else {
                $filename   = 'found_' . $uid . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['found_image']['tmp_name'], $targetPath)) {
                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO found_reports (finder_id, image_path, description) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'iss', $uid, $targetPath, $description);
                    if (mysqli_stmt_execute($stmt)) {
                        $reportId = mysqli_insert_id($conn);
                        header("Location: found_matches.php?report_id=" . $reportId);
                        exit();
                    } else {
                        $error = 'Database error: ' . mysqli_error($conn);
                    }
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Item — Back2U Lost &amp; Found</title>
<script>
/* Apply saved theme BEFORE first paint — prevents flash */
(function () {
    var t = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
<style>
/* ── CSS variables — identical to dashboard.php ── */
:root {
    --bg:#f0f2f5;--surface:#ffffff;--surface2:#f8f9fa;--border:#e0e0e0;
    --text:#1a1a1a;--text-muted:#666666;--text-faint:#999999;
    --header-bg:#003366;--header-text:#ffffff;
    --accent:#003366;--accent-hover:#004080;--shadow:rgba(0,0,0,0.10);
    --input-bg:#ffffff;--input-border:#dddddd;
}
[data-theme="dark"] {
    --bg:#0f1117;--surface:#1e2130;--surface2:#262a3a;--border:#2e3348;
    --text:#e8eaf6;--text-muted:#9fa8c0;--text-faint:#5c6480;
    --header-bg:#0d1b3e;--header-text:#e8eaf6;
    --accent:#4a80d4;--accent-hover:#5a90e4;--shadow:rgba(0,0,0,0.40);
    --input-bg:#262a3a;--input-border:#2e3348;
}

*, *::before, *::after {
    margin: 0; padding: 0; box-sizing: border-box;
    transition: background-color 0.3s, color 0.2s, border-color 0.2s;
}
body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

/* ── Header ── */
header {
    background: var(--header-bg); color: var(--header-text);
    padding: 15px 20px; display: flex; justify-content: space-between;
    align-items: center; position: sticky; top: 0; z-index: 10;
    box-shadow: 0 2px 10px var(--shadow);
}
.header-left  { display: flex; align-items: center; gap: 12px; }
.header-right { display: flex; align-items: center; gap: 15px; }
.avatar-link  { display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--header-text); }
.avatar-link img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.5); }
.avatar-link:hover img { border-color: white; }
.theme-toggle {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    color: var(--header-text); width: 40px; height: 40px; border-radius: 50%;
    cursor: pointer; font-size: 1.1rem; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0;
}
.theme-toggle:hover { background: rgba(255,255,255,0.25); }

/* ── Page layout ── */
.page { max-width: 780px; margin: 40px auto; padding: 0 20px 60px; }
.back-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--accent); text-decoration: none; font-size: 0.9rem; margin-bottom: 24px;
}
.back-link:hover { text-decoration: underline; }

/* ── Mode selector ── */
.mode-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px; }
.mode-card {
    background: var(--surface); border: 2px solid var(--border);
    border-radius: 12px; padding: 28px 20px; text-align: center;
    cursor: pointer; transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s, background-color 0.3s;
}
.mode-card:hover {
    border-color: var(--accent); transform: translateY(-2px);
    box-shadow: 0 4px 15px var(--shadow);
}
.mode-card.selected { border-color: var(--accent); background: var(--surface2); }
.mode-card .icon  { font-size: 2.5rem; margin-bottom: 10px; }
.mode-card h3     { color: var(--accent); font-size: 1.1rem; margin-bottom: 6px; }
.mode-card p      { color: var(--text-muted); font-size: 0.85rem; line-height: 1.4; }

/* ── Forms ── */
.form-card {
    background: var(--surface); border-radius: 12px;
    box-shadow: 0 2px 10px var(--shadow); padding: 32px;
    display: none; border: 1px solid var(--border);
}
.form-card.active   { display: block; }
.form-card h2       { color: var(--accent); margin-bottom: 6px; }
.form-card .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px; }
.form-group { margin-bottom: 18px; }
.form-group label {
    display: block; font-weight: 600; color: var(--text);
    margin-bottom: 6px; font-size: 0.9rem;
}
.form-group input[type=text],
.form-group textarea {
    width: 100%; padding: 11px 14px;
    border: 1px solid var(--input-border); border-radius: 6px;
    font-size: 0.95rem; font-family: inherit;
    background: var(--input-bg); color: var(--text);
}
.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--accent); outline: none;
    box-shadow: 0 0 0 3px rgba(74,128,212,0.15);
}
.form-group textarea { resize: vertical; min-height: 90px; }

/* ── Image upload zone ── */
.upload-zone {
    border: 2px dashed var(--border); border-radius: 8px; padding: 30px;
    text-align: center; cursor: pointer;
    position: relative; background: var(--surface2);
    transition: border-color 0.2s, background-color 0.3s;
}
.upload-zone:hover { border-color: var(--accent); }
.upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-zone .upload-icon { font-size: 2rem; margin-bottom: 8px; }
.upload-zone p { color: var(--text-muted); font-size: 0.88rem; }
.upload-zone.has-file { border-color: #28a745; }
.preview-img { max-width: 100%; max-height: 180px; border-radius: 6px; margin-top: 10px; display: none; }

.submit-btn {
    background: var(--accent); color: white; border: none;
    padding: 13px 32px; border-radius: 6px; font-size: 1rem;
    font-weight: 600; cursor: pointer; width: 100%; margin-top: 8px;
    transition: background 0.2s;
}
.submit-btn:hover        { background: var(--accent-hover); }
.submit-btn.green        { background: #28a745; }
.submit-btn.green:hover  { background: #218838; }

/* ── Alerts ── */
.alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
.alert-error   { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
.alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
[data-theme="dark"] .alert-error   { background: #3b1018; color: #ef9a9a; border-color: #7f2030; }
[data-theme="dark"] .alert-success { background: #0d2e18; color: #81c784; border-color: #1b5e2e; }

/* ── Info box ── */
.info-box {
    background: #e3f2fd; border: 1px solid #90caf9; border-radius: 6px;
    padding: 12px 14px; margin-bottom: 20px; font-size: 0.85rem;
    color: #1565c0; line-height: 1.5;
}
[data-theme="dark"] .info-box { background: #0d2a4a; border-color: #1e4a7a; color: #90caf9; }

.page-heading    { color: var(--accent); margin-bottom: 6px; }
.page-subheading { color: var(--text-muted); margin-bottom: 24px; font-size: 0.92rem; }
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="nmims-university-logo.png" alt="NMIMS" style="height:42px;filter:brightness(0) invert(1);object-fit:contain;">
    <h3>Back2U</h3>
  </div>
  <div class="header-right">
    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle dark mode">🌙</button>
    <a href="profile.php" class="avatar-link">
      <img src="<?php echo $avatarSrc ?: $fallbackAvatar; ?>"
           alt="Profile"
           onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>'">
      <span><?php echo htmlspecialchars($pRow['name'] ?? 'Profile'); ?></span>
    </a>
    <a href="logout.php" style="color:var(--header-text);text-decoration:none;">Logout</a>
  </div>
</header>

<div class="page">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
  <h2 class="page-heading">What would you like to do?</h2>
  <p class="page-subheading">Choose an option below to continue.</p>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <!-- Mode selector cards -->
  <div class="mode-selector">
    <div class="mode-card" id="card-register" onclick="selectMode('register')">
      <div class="icon">📋</div>
      <h3>Register an Item</h3>
      <p>I own an item and want to register it so I can report it lost later if needed.</p>
    </div>
    <div class="mode-card" id="card-found" onclick="selectMode('found')">
      <div class="icon">🔍</div>
      <h3>I Found an Item</h3>
      <p>I found something that isn't mine. I'll upload it so the owner can identify it.</p>
    </div>
  </div>

  <!-- Form A: Register an Item -->
  <div class="form-card" id="form-register">
    <h2>📋 Register Your Item</h2>
    <p class="subtitle">Register your item now. If it gets lost later, you can mark it as lost from My Items.</p>
    <div class="info-box">
      ℹ️ Registered items are only visible to you in <strong>My Items</strong>. They won't appear in the public lost list until you click <strong>"Mark as Lost"</strong>.
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="mode" value="register">
      <div class="form-group">
        <label>Item Name / Title *</label>
        <input type="text" name="title" placeholder="e.g. Blue Sony Headphones" required>
      </div>
      <div class="form-group">
        <label>Description *</label>
        <textarea name="description" placeholder="Describe your item — colour, brand, distinguishing marks..." required></textarea>
      </div>
      <div class="form-group">
        <label>Photo of Item *</label>
        <div class="upload-zone" id="zone-register">
          <input type="file" name="image" accept="image/*" onchange="previewImage(this,'preview-register','zone-register')">
          <div class="upload-icon">📷</div>
          <p>Click or drag to upload an image</p>
          <img class="preview-img" id="preview-register" alt="Preview">
        </div>
      </div>
      <button type="submit" class="submit-btn">Register Item</button>
    </form>
  </div>

  <!-- Form B: Found Report -->
  <div class="form-card" id="form-found">
    <h2>🔍 Report a Found Item</h2>
    <p class="subtitle">Describe and photograph what you found. We'll show you lost items that may match.</p>
    <div class="info-box">
      ℹ️ After submitting, you'll see a list of <strong>lost items</strong> registered by other users. Manually check if any match what you found, then chat with the owner.
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="mode" value="found_report">
      <div class="form-group">
        <label>One-line description of what you found *</label>
        <input type="text" name="found_description" placeholder="e.g. Black leather wallet near Library Block B" required>
      </div>
      <div class="form-group">
        <label>Photo of the found item *</label>
        <div class="upload-zone" id="zone-found">
          <input type="file" name="found_image" accept="image/*" onchange="previewImage(this,'preview-found','zone-found')">
          <div class="upload-icon">📷</div>
          <p>Click or drag to upload an image</p>
          <img class="preview-img" id="preview-found" alt="Preview">
        </div>
      </div>
      <button type="submit" class="submit-btn green">Submit &amp; See Potential Matches →</button>
    </form>
  </div>
</div>

<script>
/* ── Theme — synced with dashboard.php via localStorage key 'theme' ── */
function toggleTheme() {
    var html   = document.documentElement;
    var isDark = html.getAttribute('data-theme') === 'dark';
    var next   = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.getElementById('themeToggle').textContent = next === 'dark' ? '☀️' : '🌙';
    localStorage.setItem('theme', next);
}
/* Sync icon with saved theme on load */
(function () {
    var saved = localStorage.getItem('theme') || 'light';
    document.getElementById('themeToggle').textContent = saved === 'dark' ? '☀️' : '🌙';
})();

/* ── Mode selector ── */
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
selectMode('<?php echo ($_POST['mode'] ?? '') === 'found_report' ? 'found' : 'register'; ?>');
<?php endif; ?>

function selectMode(mode) {
    document.querySelectorAll('.mode-card').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.form-card').forEach(f => f.classList.remove('active'));
    document.getElementById('card-' + mode).classList.add('selected');
    document.getElementById('form-' + mode).classList.add('active');
}

function previewImage(input, previewId, zoneId) {
    const preview = document.getElementById(previewId);
    const zone    = document.getElementById(zoneId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            zone.classList.add('has-file');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>