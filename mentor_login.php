<?php
session_start();
include __DIR__ . '/../db_connect.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM mentors WHERE email=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $mentor = $result->fetch_assoc();
    if (password_verify($password, $mentor['password'])) {
      $_SESSION['mentor_id'] = $mentor['id'];
      $_SESSION['mentor_name'] = $mentor['name'];
      header("Location: mentor_dashboard.php");
      exit();
    } else {
      $message = "❌ Invalid password.";
    }
  } else {
    $message = "❌ No account found with that email.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mentor Login</title>
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

    .back-home {
      display: block;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="form-box">
      <h2>👨‍🏫 Mentor Login</h2>
      <p class="subtitle">Enter your account details to access your mentor dashboard.</p>

      <form method="POST" autocomplete="off">
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" name="email" id="email" required>
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
      </form>

      <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
      <?php endif; ?>

      <p class="footer-text">
        Don't have an account? <a href="mentor_register.php">Register here</a><br>
        <a href="../index.php" class="back-home">← Back to Home</a>
      </p>
    </div>
  </div>

</body>
</html>
