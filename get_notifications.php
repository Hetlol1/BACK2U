<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread' => 0, 'notifications' => []]);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Mark as read if requested
if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$uid");
}

// Get latest 10 notifications
$stmt = mysqli_prepare($conn,
    "SELECT n.*, i.title AS item_title
     FROM notifications n
     LEFT JOIN items i ON n.item_id = i.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT 10");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

// Unread count
$cStmt = mysqli_prepare($conn,
    "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0");
mysqli_stmt_bind_param($cStmt, 'i', $uid);
mysqli_stmt_execute($cStmt);
$unread = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt))['cnt'];

echo json_encode(['unread' => (int)$unread, 'notifications' => $notifications]);
?>
