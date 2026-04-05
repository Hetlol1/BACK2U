<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$item_id = (int)($_POST['item_id'] ?? 0);

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
$found_by = (int)($item['found_by'] ?? 0);
$status   = $item['status'];

if ($status === 'found') {
    if ($user_id === $owner_id) {
        echo json_encode(['status' => 'error', 'message' => 'You uploaded this item — you cannot claim your own upload.']);
        exit();
    }
    $ok = mysqli_query($conn,
        "UPDATE items SET status='pending', found_by='$user_id' WHERE id='$item_id'"
    );
    if ($ok) {
        echo json_encode(['status' => 'success', 'message' => 'Claim submitted! Waiting for the finder to confirm.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

if ($status === 'pending') {
    if ($user_id !== $owner_id) {
        echo json_encode(['status' => 'error', 'message' => 'Only the item owner can confirm the claim.']);
        exit();
    }
    $ok = mysqli_query($conn,
        "UPDATE items SET status='claimed' WHERE id='$item_id'"
    );
    if ($ok) {
        // ── Notify the finder that claim is confirmed ──
        $ownerName = $_SESSION['name'] ?? 'The owner';
        $itemTitle = $item['title'] ?? 'the item';
        $message   = "{$ownerName} confirmed the return of \"{$itemTitle}\". Thank you for helping! 🎉";
        $type      = 'claim_confirmed';

        if ($found_by) {
            $nStmt = mysqli_prepare($conn,
                "INSERT INTO notifications (user_id, type, message, item_id) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($nStmt, 'issi', $found_by, $type, $message, $item_id);
            mysqli_stmt_execute($nStmt);
        }

        echo json_encode(['status' => 'success', 'message' => 'Item marked as claimed. All done! 🎉']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'This item cannot be claimed (status: ' . htmlspecialchars($status) . ').']);
?>
