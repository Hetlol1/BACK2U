<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$item_id = intval($_GET['item_id'] ?? 0);

if ($item_id > 0) {
    $sql  = "SELECT m.*, u.name AS sender_name
             FROM messages m
             LEFT JOIN users u ON m.sender_id = u.id
             WHERE m.item_id = ?
             ORDER BY m.created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id'          => $row['id'],
            'item_id'     => $row['item_id'],
            'sender_id'   => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'message'     => $row['message'],
            'created_at'  => $row['created_at']
        ];
    }

    echo json_encode($messages);
} else {
    echo json_encode([]);
}
?>