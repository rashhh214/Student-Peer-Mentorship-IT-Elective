<?php
include '../db_connect.php';
include '../email_config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // Insert mentor record
  $sql = "INSERT INTO mentors (name, email, password) VALUES (?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss", $name, $email, $password);

  if ($stmt->execute()) {

    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $conn->query("UPDATE mentors SET verify_token='$token' WHERE email='$email'");

    // Verification link
    $verify_link = "http://localhost/studentpeermentorship/mentor/verify.php?token=$token";

    /* ============================
       SEND EMAIL TO MENTOR
    ============================= */
    $email_body = "
    <div style='font-family:Arial;padding:20px;background:#f0f0f0;'>
      <div style='background:#fff;padding:20px;border-radius:8px;'>
        <h2 style='color:#2c7be5;'>Welcome, $name!</h2>
        <p>Thank you for registering as a <b>Mentor</b> in the Student Peer Mentorship System.</p>
        <p>Please verify your email to activate your account:</p>
        
        <a href='$verify_link'
           style='display:inline-block;background:#2c7be5;color:white;padding:12px 18px;
                  text-decoration:none;border-radius:6px;margin-top:10px;'>
          Verify My Account
        </a>

        <p style='margin-top:25px;font-size:12px;color:#555;'>
          If you did not create this account, please ignore this message.
        </p>
      </div>
    </div>
    ";

    sendMail($email, "Verify Your Mentor Account", $email_body);


    /* ============================
       SEND NOTIFICATION EMAIL TO ADMIN
    ============================= */

    $admin_email = "rasheedomar194@gmail.com"; // <-- CHANGE THIS

    $admin_body = "
    <h3>New Mentor Registration</h3>
    <p>A new mentor has registered:</p>
    <ul>
      <li><b>Name:</b> $name</li>
      <li><b>Email:</b> $email</li>
    </ul>
    <p>Login to admin dashboard to review.</p>
    ";

    sendMail($admin_email, "New Mentor Registration Alert", $admin_body);


    /* ============================
       SHOW SUCCESS MESSAGE
    ============================= */

    $message = "Account created! Please check your email to verify your mentor account.";

  } else {
    $message = "Error: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mentor Registration</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0097e6, #00a8ff, #273c75);
      min-height: 100vh;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #2f3640;
    }
    .container {
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }
    .form-box {
      background: #ffffff;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      padding: 40px 30px;
      text-align: center;
    }
    .form-box h2 {
      margin-bottom: 10px;
      color: #273c75;
    }
    .subtitle {
      color: #718093;
      font-size: 0.9rem;
      margin-bottom: 25px;
    }
    .input-group {
      text-align: left;
      margin-bottom: 20px;
    }
    .input-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 5px;
    }
    .input-group input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #dcdde1;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }
    .input-group input:focus {
      border-color: #0097e6;
      outline: none;
      box-shadow: 0 0 4px rgba(0,151,230,0.3);
    }
    .btn {
      background: #0097e6;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 12px 0;
      font-size: 1.1rem;
      width: 100%;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn:hover {
      background: #00a8ff;
    }
    .message {
      background: #dcdde1;
      padding: 10px;
      border-radius: 6px;
      margin-top: 15px;
      font-size: 0.9rem;
    }
    .footer-text {
      margin-top: 20px;
      font-size: 0.9rem;
      color: #718093;
    }
    .footer-text a {
      color: #0097e6;
      text-decoration: none;
      font-weight: 600;
    }
    .footer-text a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="form-box">
      <h2>👨‍🏫 Mentor Registration</h2>
      <p class="subtitle">Create your mentor account to start guiding students.</p>

      <form method="POST" autocomplete="off">
        <div class="input-group">
          <label for="name">Full Name</label>
          <input type="text" name="name" id="name" required>
        </div>

        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" name="email" id="email" required>
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" required minlength="6">
        </div>

        <button type="submit" class="btn">Register</button>
      </form>

      <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
      <?php endif; ?>

      <p class="footer-text">
        Already have an account? <a href="mentor_login.php">Login here</a><br>
        <a href="../index.php">← Back to Home</a>
      </p>
    </div>
  </div>

</body>
</html>