<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "nmims_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('GEMINI_API_KEY', 'AIzaSyBor5dI_EDgdfk9fNm6m83dtYZgPhEY16o');  // ← paste new key here

session_start();
?>