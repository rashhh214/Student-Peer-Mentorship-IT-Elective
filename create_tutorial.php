<?php
session_start();
include __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['mentor_id'])) {
  header("Location: mentor_login.php");
  exit();
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $title = $_POST['title'];
  $desc = $_POST['description'];
  $topic = $_POST['topic'];
  $schedule = $_POST['schedule'];
  $duration = $_POST['duration'];
  $is_public = isset($_POST['is_public']) ? 1 : 0;
  $mentor_id = $_SESSION['mentor_id'];

  $sql = "INSERT INTO tutorials (mentor_id, title, description, topic, schedule, duration, is_public) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("isssssi", $mentor_id, $title, $desc, $topic, $schedule, $duration, $is_public);

  if ($stmt->execute()) {
    $message = "✅ Tutorial created successfully!";
  } else {
    $message = "❌ Error: " . $conn->error;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Tutorial</title>
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
    max-width: 500px;
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
    margin-bottom: 15px;
    color: #273c75;
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

  .input-group input,
  .input-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dcdde1;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }

  .input-group input:focus,
  .input-group textarea:focus {
    border-color: #0097e6;
    outline: none;
    box-shadow: 0 0 4px rgba(0,151,230,0.3);
  }

  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-weight: 600;
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

  .back-link {
    display: block;
    margin-top: 20px;
    color: #0097e6;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
  }

  .back-link:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="container">
  <div class="form-box">
    <h2>➕ Create Tutorial Session</h2>

    <form method="POST" autocomplete="off">
      <div class="input-group">
        <label for="title">Title</label>
        <input type="text" name="title" id="title" required>
      </div>

      <div class="input-group">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4" required></textarea>
      </div>

      <div class="input-group">
        <label for="topic">Topic</label>
        <input type="text" name="topic" id="topic" required>
      </div>

      <div class="input-group">
        <label for="schedule">Schedule</label>
        <input type="datetime-local" name="schedule" id="schedule" required>
      </div>

      <div class="input-group">
        <label for="duration">Duration</label>
        <input type="text" name="duration" id="duration" placeholder="e.g., 1 hour" required>
      </div>

      <div class="checkbox-group">
        <input type="checkbox" name="is_public" id="is_public" checked>
        <label for="is_public">Public</label>
      </div>

      <button type="submit" class="btn">Create Tutorial</button>
    </form>

    <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <a href="mentor_dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>
</div>

</body>
</html>
