<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$item_id = intval($_POST['item_id'] ?? 0);
$uid     = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'user';

if ($item_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
    exit;
}

$check = mysqli_query($conn, "SELECT * FROM items WHERE id='$item_id'");

if (mysqli_num_rows($check) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    exit;
}

$item = mysqli_fetch_assoc($check);

// Allow deletion by owner OR admin
if ($item['owner_id'] != $uid && $role !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'You are not the owner of this item']);
    exit;
}

// Messages are deleted automatically via ON DELETE CASCADE
// but delete manually here as a safety net
mysqli_query($conn, "DELETE FROM messages WHERE item_id = '$item_id'");
mysqli_query($conn, "DELETE FROM notifications WHERE item_id = '$item_id'");

if (mysqli_query($conn, "DELETE FROM items WHERE id = '$item_id'")) {
    echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete: ' . mysqli_error($conn)]);
}
?>
