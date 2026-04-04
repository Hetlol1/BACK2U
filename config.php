<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "nmims_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('GEMINI_API_KEY', 'AIzaSyBz-vof6bWGoTgSCjaoAlk_oLhXs9nTWeo');  // ← paste new key here

session_start();
?>