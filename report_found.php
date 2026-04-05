<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$finder_id = (int)$_SESSION['user_id'];
$item_id   = (int)($_POST['item_id'] ?? 0);

if (!$item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID.']);
    exit();
}

$res  = mysqli_query($conn, "SELECT * FROM items WHERE id='$item_id'");
$item = mysqli_fetch_assoc($res);

if (!$item) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
    exit();
}

$owner_id = (int)($item['user_id'] ?? $item['owner_id'] ?? 0);

if ($owner_id === $finder_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot report your own item as found.']);
    exit();
}

if ($item['status'] !== 'lost') {
    echo json_encode(['status' => 'error', 'message' => 'This item is no longer marked as lost (status: ' . $item['status'] . ').']);
    exit();
}

$update = mysqli_query($conn,
    "UPDATE items SET status='pending', found_by='$finder_id' WHERE id='$item_id'"
);

if ($update) {
    // ── Notify the item owner ──
    $finderName = $_SESSION['name'] ?? 'Someone';
    $itemTitle  = $item['title'] ?? 'your item';
    $message    = "{$finderName} reported finding \"{$itemTitle}\". Please confirm if it's yours.";
    $type       = 'item_found';

    $nStmt = mysqli_prepare($conn,
        "INSERT INTO notifications (user_id, type, message, item_id) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($nStmt, 'issi', $owner_id, $type, $message, $item_id);
    mysqli_stmt_execute($nStmt);

    echo json_encode(['status' => 'success', 'message' => 'Reported! Waiting for the owner to confirm.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>
