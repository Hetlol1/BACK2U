<?php
include 'config.php';
header('Content-Type: text/plain');

$action = $_POST['action'] ?? '';

function send_response($message) {
    echo $message;
    exit;
}

// ── Domain validation ──────────────────────────────────────
function isAllowedDomain($email, $conn) {
    $parts  = explode('@', $email);
    if (count($parts) !== 2) return false;
    $domain = strtolower(trim($parts[1]));

    // Auto-allow any official Indian/international academic pattern
    if (preg_match('/\.(ac\.in|edu\.in|edu|res\.in)$/', $domain)) {
        return true;
    }

    // Check custom approved domains in DB
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM allowed_domains WHERE domain = ? AND approved = 1");
    mysqli_stmt_bind_param($stmt, 's', $domain);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $found;
}

// ── 1. REGISTRATION ────────────────────────────────────────
if ($action === 'register') {
    $name  = trim($_POST['name']     ?? '');
    $email = trim($_POST['email']    ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($pass)) {
        send_response("Please fill all fields");
    }

    // Domain check
    if (!isAllowedDomain($email, $conn)) {
        send_response("Only college email addresses are allowed to register (e.g. @nmims.edu, @iitb.ac.in)");
    }

    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $role   = 'user'; // admins cannot self-register

    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, 's', $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) > 0) {
        send_response("Email already exists");
    }
    mysqli_stmt_close($check);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hashed, $role);

    if (mysqli_stmt_execute($stmt)) {
        send_response("Registration Successful");
    } else {
        send_response("Database Error: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);
}

// ── 2. LOGIN ───────────────────────────────────────────────
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $pass     = trim($_POST['password'] ?? '');
    $login_as = trim($_POST['login_as'] ?? 'user');

    $stmt = mysqli_prepare($conn,
        "SELECT id, name, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($pass, $row['password'])) {

            if ($login_as === 'admin' && $row['role'] !== 'admin') {
                send_response("Access denied. You are not an admin.");
            }
            if ($login_as === 'user' && $row['role'] === 'admin') {
                send_response("Please use the Admin login.");
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name']    = $row['name'];
            $_SESSION['role']    = $row['role'];

            if ($row['role'] === 'admin') {
                send_response("redirect:admin_dashboard.php");
            } else {
                send_response("redirect:dashboard.php");
            }

        } else {
            send_response("Invalid Password");
        }
    } else {
        send_response("User not found");
    }
    mysqli_stmt_close($stmt);
}
?>
