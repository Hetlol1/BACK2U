<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$uid    = (int)$_SESSION['user_id'];
$role   = $_SESSION['role']   ?? 'user';
$domain = $_SESSION['domain'] ?? '';

// If domain not in session yet (old login), derive it from DB
if (empty($domain)) {
    $r = mysqli_query($conn, "SELECT email FROM users WHERE id=$uid");
    $row = mysqli_fetch_assoc($r);
    if ($row && strpos($row['email'], '@') !== false) {
        $domain = strtolower(explode('@', $row['email'])[1]);
        $_SESSION['domain'] = $domain;
    }
}

$view = $_GET['view'] ?? 'all';

if ($view === 'mine') {
    // My Items — all statuses, only this user's items
    $sql  = "SELECT i.*, u.name AS owner_name
             FROM items i
             LEFT JOIN users u ON i.owner_id = u.id
             WHERE i.owner_id = ?
             ORDER BY i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $uid);

} elseif ($role === 'admin') {
    // Admins see ALL items across all colleges
    $sql  = "SELECT i.*, u.name AS owner_name
             FROM items i
             LEFT JOIN users u ON i.owner_id = u.id
             WHERE i.status IN ('lost','pending','claimed')
             ORDER BY i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);

} else {
    // Regular users — only see lost/pending/claimed from their own college domain
    $sql  = "SELECT i.*, u.name AS owner_name
             FROM items i
             LEFT JOIN users u ON i.owner_id = u.id
             WHERE i.status IN ('lost','pending','claimed')
             AND i.college_domain = ?
             ORDER BY i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $domain);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode($items);
?>
