<?php
include '../db_connect.php';
include '../email_config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // Insert user
  $sql = "INSERT INTO students (name, email, password) VALUES (?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss", $name, $email, $password);

  if ($stmt->execute()) {

    // Create verification token
    $token = bin2hex(random_bytes(32));
    $conn->query("UPDATE students SET verify_token='$token' WHERE email='$email'");

    // Verification link
    $verify_link = "http://localhost/studentpeermentorship/student/verify.php?token=$token";

    // Send email
    sendMail(
      $email,
      "Verify Your Student Account",
      "
      <h2>Hello, $name!</h2>
      <p>Click the link below to verify your account:</p>
      <a href='$verify_link' style='background:#0057ff;color:white;padding:10px 15px;border-radius:6px;text-decoration:none;'>Verify Account</a>
      "
    );

    $message = "Account created! Please check your email to verify.";

  } else {
    $message = "Error: " . $conn->error;
  }
}
?>
