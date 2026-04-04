<?php
include 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'check_email') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email required']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['status' => 'found']);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit;
}

if ($action === 'reset_password') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password too short']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $hashed, $email);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed or email not found']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>