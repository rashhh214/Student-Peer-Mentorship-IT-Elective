<?php
session_start();
include __DIR__ . '/../db_connect.php';
include __DIR__ . '/../email_config.php';
include __DIR__ . '/mentor_notification_functions.php';

if (!isset($_SESSION['mentor_id'])) {
  header("Location: mentor_login.php");
  exit();
}

$mentor_id = $_SESSION['mentor_id'];
$tutorial_id = isset($_GET['tutorial_id']) ? intval($_GET['tutorial_id']) : 0;

if ($tutorial_id == 0) {
  die("Invalid tutorial ID.");
}

$tutorial_check = $conn->prepare("SELECT * FROM tutorials WHERE id=? AND mentor_id=?");
$tutorial_check->bind_param("ii", $tutorial_id, $mentor_id);
$tutorial_check->execute();
$tutorial_result = $tutorial_check->get_result();

if ($tutorial_result->num_rows == 0) {
  die("Tutorial not found or access denied.");
}

$tutorial = $tutorial_result->fetch_assoc();

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['approve'])) {
    $signup_id = intval($_POST['signup_id']);
    $student_email = $_POST['student_email'];
    $student_name = $_POST['student_name'];
    
    $stmt = $conn->prepare("UPDATE tutorial_signups SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $signup_id);
    
    if ($stmt->execute()) {
      // Check if tutorial is getting full (e.g., 10+ students)
      $count_result = $conn->query("
        SELECT COUNT(*) as count 
        FROM tutorial_signups 
        WHERE tutorial_id = $tutorial_id AND status = 'approved'
      ");
      $student_count = $count_result->fetch_assoc()['count'];
      
      // Notify if tutorial reaches 10, 20, 30 students
      if ($student_count % 10 == 0) {
        notifyTutorialFull($mentor_id, $tutorial_id, $tutorial['title'], $student_count);
      }
      
      // Send approval email
      $email_body = "
      <div style='font-family:Arial;padding:20px;background:#f0f0f0;'>
        <div style='background:#fff;padding:20px;border-radius:8px;'>
          <h2 style='color:#44bd32;'>Tutorial Registration Approved! ✅</h2>
          <p>Hello <b>$student_name</b>,</p>
          <p>Your registration for the tutorial has been <b>approved</b>:</p>
          <div style='background:#f5f6fa;padding:15px;border-radius:6px;margin:15px 0;'>
            <p><b>Tutorial:</b> {$tutorial['title']}</p>
            <p><b>Topic:</b> {$tutorial['topic']}</p>
            <p><b>Schedule:</b> {$tutorial['schedule']}</p>
            <p><b>Duration:</b> {$tutorial['duration']}</p>
          </div>
          <p>Please mark your calendar and prepare for the session.</p>
        </div>
      </div>
      ";
      
      $email_result = sendMail($student_email, "Tutorial Registration Approved", $email_body);
      
      if ($email_result === true) {
        $message = "✅ Student approved and notified via email!";
      } else {
        $message = "✅ Student approved (Email notification failed: $email_result)";
      }
    }
    
  } elseif (isset($_POST['reject'])) {
    $signup_id = intval($_POST['signup_id']);
    $student_email = $_POST['student_email'];
    $student_name = $_POST['student_name'];
    
    $stmt = $conn->prepare("UPDATE tutorial_signups SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $signup_id);
    
    if ($stmt->execute()) {
      $email_body = "
      <div style='font-family:Arial;padding:20px;background:#f0f0f0;'>
        <div style='background:#fff;padding:20px;border-radius:8px;'>
          <h2 style='color:#e84118;'>Tutorial Registration Update</h2>
          <p>Hello <b>$student_name</b>,</p>
          <p>We regret to inform you that your registration for the following tutorial could not be approved at this time:</p>
          <div style='background:#f5f6fa;padding:15px;border-radius:6px;margin:15px 0;'>
            <p><b>Tutorial:</b> {$tutorial['title']}</p>
            <p><b>Topic:</b> {$tutorial['topic']}</p>
          </div>
          <p>Please feel free to register for other available tutorials.</p>
        </div>
      </div>
      ";
      
      $email_result = sendMail($student_email, "Tutorial Registration Update", $email_body);
      
      if ($email_result === true) {
        $message = "❌ Student rejected and notified via email.";
      } else {
        $message = "❌ Student rejected (Email notification failed: $email_result)";
      }
    }
  }
}

$signups = $conn->query("
  SELECT ts.id, ts.status, ts.signup_date, s.id as student_id, s.name, s.email 
  FROM tutorial_signups ts
  JOIN students s ON ts.student_id = s.id
  WHERE ts.tutorial_id = $tutorial_id
  ORDER BY ts.signup_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Students - <?php echo htmlspecialchars($tutorial['title']); ?></title>
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0097e6, #00a8ff, #273c75);
    min-height: 100vh;
    margin: 0;
    padding: 20px;
    color: #2f3640;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    color: #fff;
  }

  .header h2 {
    margin: 0;
  }

  .back-link {
    color: white;
    text-decoration: none;
    font-weight: 600;
    background: #0097e6;
    padding: 8px 14px;
    border-radius: 6px;
    transition: 0.3s;
  }

  .back-link:hover {
    background: #00a8ff;
  }

  .card {
    background: #ffffff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 1200px;
    margin: auto;
  }

  .tutorial-info {
    background: #f5f6fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
  }

  .tutorial-info h3 {
    color: #0097e6;
    margin-bottom: 10px;
  }

  .tutorial-info p {
    margin: 5px 0;
    color: #2f3640;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
  }

  table th, table td {
    border: 1px solid #dcdde1;
    padding: 12px;
    text-align: left;
  }

  table th {
    background: #0097e6;
    color: white;
    font-weight: 600;
  }

  table tr:nth-child(even) {
    background: #f5f6fa;
  }

  .action-form {
    display: inline-flex;
    gap: 8px;
  }

  .btn {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: 0.3s;
  }

  .approve-btn {
    background: #44bd32;
  }

  .approve-btn:hover {
    background: #4cd137;
  }

  .reject-btn {
    background: #e84118;
  }

  .reject-btn:hover {
    background: #c23616;
  }

  .status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
  }

  .status-pending {
    background: #ffa502;
    color: white;
  }

  .status-approved {
    background: #44bd32;
    color: white;
  }

  .status-rejected {
    background: #e84118;
    color: white;
  }

  .message {
    background: #dfe4ea;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
  }

  .no-data {
    text-align: center;
    color: #718093;
    padding: 40px;
    font-size: 1.1rem;
  }
</style>
</head>
<body>

<div class="header">
  <h2>📋 Students Enrolled</h2>
  <a href="mentor_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

<div class="card">
  <?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
  <?php endif; ?>

  <div class="tutorial-info">
    <h3><?php echo htmlspecialchars($tutorial['title']); ?></h3>
    <p><strong>Topic:</strong> <?php echo htmlspecialchars($tutorial['topic']); ?></p>
    <p><strong>Schedule:</strong> <?php echo htmlspecialchars($tutorial['schedule']); ?></p>
    <p><strong>Duration:</strong> <?php echo htmlspecialchars($tutorial['duration']); ?></p>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($tutorial['description']); ?></p>
  </div>

  <h3>Student Registrations</h3>

  <?php if ($signups->num_rows > 0): ?>
    <table>
      <tr>
        <th>Student Name</th>
        <th>Email</th>
        <th>Signup Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
      <?php while($row = $signups->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['email']); ?></td>
          <td><?php echo date('M d, Y g:i A', strtotime($row['signup_date'])); ?></td>
          <td>
            <?php 
            $status = $row['status'];
            $badge_class = 'status-' . $status;
            echo "<span class='status-badge $badge_class'>" . ucfirst($status) . "</span>";
            ?>
          </td>
          <td>
            <?php if ($row['status'] == 'pending'): ?>
              <form method="POST" class="action-form">
                <input type="hidden" name="signup_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($row['email']); ?>">
                <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                <button type="submit" name="approve" class="btn approve-btn">✓ Approve</button>
                <button type="submit" name="reject" class="btn reject-btn">✗ Reject</button>
              </form>
            <?php else: ?>
              <span style="color: #718093;">No action needed</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <div class="no-data">
      No students have registered for this tutorial yet.
    </div>
  <?php endif; ?>
</div>

</body>
</html>