<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $domain  = strtolower(trim($_POST['domain']  ?? ''));
    $college = trim($_POST['college'] ?? '');
    if (empty($domain)) {
        echo json_encode(['status'=>'error','message'=>'Domain is required']); exit;
    }
    $stmt = mysqli_prepare($conn,
        "INSERT INTO allowed_domains (domain, college, approved) VALUES (?, ?, 1)");
    mysqli_stmt_bind_param($stmt, 'ss', $domain, $college);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status'=>'success','message'=>"Domain @{$domain} added successfully."]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Domain already exists or DB error.']);
    }
    exit;
}

if ($action === 'approve') {
    $id = intval($_POST['id'] ?? 0);
    mysqli_query($conn, "UPDATE allowed_domains SET approved=1 WHERE id=$id");
    echo json_encode(['status'=>'success','message'=>'Domain approved.']);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    mysqli_query($conn, "DELETE FROM allowed_domains WHERE id=$id");
    echo json_encode(['status'=>'success','message'=>'Domain removed.']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Invalid action']);
?>
