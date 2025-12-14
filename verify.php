<?php
include '../db_connect.php';

if (isset($_GET['token'])) {

    $token = $_GET['token'];

    $sql = "SELECT * FROM mentors WHERE verify_token='$token'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $conn->query("UPDATE mentors SET is_verified=1, verify_token='' WHERE verify_token='$token'");
        echo "<h2>Your mentor account has been verified!</h2>";
        echo "<a href='mentor_login.php'>Login</a>";
    } else {
        echo "<h2>Invalid or expired verification link.</h2>";
    }
}
?>
