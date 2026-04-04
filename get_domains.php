<?php
include 'config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]); exit;
}
$result = mysqli_query($conn, "SELECT * FROM allowed_domains ORDER BY created_at DESC");
$domains = [];
while ($row = mysqli_fetch_assoc($result)) $domains[] = $row;
echo json_encode($domains);
?>
