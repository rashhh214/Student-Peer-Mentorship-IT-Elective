<?php
session_start();
include '../db_connect.php';
include '../email_config.php';
include '../mentor/mentor_notification_functions.php'; // Include notification functions

if (!isset($_SESSION['student_id'])) {
  header("Location: student_login.php");
  exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

$student_result = $conn->query("SELECT email FROM students WHERE id=$student_id");
$student_data = $student_result->fetch_assoc();
$student_email = $student_data['email'];

$message = '';

if (isset($_GET['join'])) {
  $tutorial_id = intval($_GET['join']);
  
  $check = $conn->query("SELECT * FROM tutorial_signups WHERE student_id=$student_id AND tutorial_id=$tutorial_id");
  
  if ($check->num_rows == 0) {
    // Insert signup
    $conn->query("INSERT INTO tutorial_signups (tutorial_id, student_id, status) VALUES ($tutorial_id, $student_id, 'pending')");
    
    // Get tutorial and mentor details
    $tutorial_info = $conn->query("
      SELECT t.*, m.id as mentor_id, m.name AS mentor_name, m.email AS mentor_email 
      FROM tutorials t 
      JOIN mentors m ON t.mentor_id = m.id 
      WHERE t.id = $tutorial_id
    ")->fetch_assoc();
    
    // CREATE MENTOR NOTIFICATION
    notifyStudentSignup(
        $tutorial_info['mentor_id'], 
        $tutorial_id, 
        $student_name, 
        $tutorial_info['title']
    );
    
    // Send email to student
    $student_email_body = "
    <div style='font-family:Arial;padding:20px;background:#f0f0f0;'>
      <div style='background:#fff;padding:20px;border-radius:8px;'>
        <h2 style='color:#0097e6;'>Tutorial Registration Confirmed! 📚</h2>
        <p>Hello <b>$student_name</b>,</p>
        <p>You have successfully registered for the following tutorial:</p>
        <div style='background:#f5f6fa;padding:15px;border-radius:6px;margin:15px 0;'>
          <p><b>Tutorial:</b> {$tutorial_info['title']}</p>
          <p><b>Topic:</b> {$tutorial_info['topic']}</p>
          <p><b>Schedule:</b> {$tutorial_info['schedule']}</p>
          <p><b>Duration:</b> {$tutorial_info['duration']}</p>
          <p><b>Mentor:</b> {$tutorial_info['mentor_name']}</p>
        </div>
        <p><b>Status:</b> Pending approval from mentor</p>
      </div>
    </div>
    ";
    
    sendMail($student_email, "Tutorial Registration Received", $student_email_body);
    
    // Send email to mentor
    $mentor_email_body = "
    <div style='font-family:Arial;padding:20px;background:#f0f0f0;'>
      <div style='background:#fff;padding:20px;border-radius:8px;'>
        <h2 style='color:#0097e6;'>New Student Registration 🎓</h2>
        <p>Hello <b>{$tutorial_info['mentor_name']}</b>,</p>
        <p>A new student has registered for your tutorial:</p>
        <div style='background:#f5f6fa;padding:15px;border-radius:6px;margin:15px 0;'>
          <p><b>Student Name:</b> $student_name</p>
          <p><b>Student Email:</b> $student_email</p>
          <p><b>Tutorial:</b> {$tutorial_info['title']}</p>
          <p><b>Topic:</b> {$tutorial_info['topic']}</p>
        </div>
        <p>Please log in to your dashboard to approve or reject this registration.</p>
      </div>
    </div>
    ";
    
    sendMail($tutorial_info['mentor_email'], "New Student Registration", $mentor_email_body);
    
    $message = "✅ Successfully registered! The mentor has been notified.";
  } else {
    $message = "⚠️ You are already registered for this tutorial.";
  }
  
} elseif (isset($_GET['withdraw'])) {
  $tutorial_id = intval($_GET['withdraw']);
  
  // Get tutorial info before deleting
  $tutorial_info = $conn->query("
    SELECT t.title, t.mentor_id, m.name as mentor_name
    FROM tutorials t
    JOIN mentors m ON t.mentor_id = m.id
    WHERE t.id = $tutorial_id
  ")->fetch_assoc();
  
  // Delete signup
  $conn->query("DELETE FROM tutorial_signups WHERE student_id=$student_id AND tutorial_id=$tutorial_id");
  
  // CREATE WITHDRAWAL NOTIFICATION FOR MENTOR
  if ($tutorial_info) {
      notifyStudentWithdrawal(
          $tutorial_info['mentor_id'],
          $tutorial_id,
          $student_name,
          $tutorial_info['title']
      );
  }
  
  $message = "❌ Successfully withdrawn from the tutorial. The mentor has been notified.";
}

$tutorials = $conn->query("
  SELECT t.*, m.name AS mentor_name,
  (SELECT COUNT(*) FROM tutorial_signups WHERE tutorial_id = t.id AND status='approved') as enrolled_count
  FROM tutorials t 
  JOIN mentors m ON t.mentor_id = m.id 
  WHERE t.is_public=1
  ORDER BY t.schedule ASC
");

$my_signups = $conn->query("
  SELECT ts.*, t.title, t.topic, t.schedule, t.duration, m.name as mentor_name
  FROM tutorial_signups ts
  JOIN tutorials t ON ts.tutorial_id = t.id
  JOIN mentors m ON t.mentor_id = m.id
  WHERE ts.student_id=$student_id
  ORDER BY ts.signup_date DESC
");

$signed_ids = [];
$my_signups_temp = $conn->query("SELECT tutorial_id FROM tutorial_signups WHERE student_id=$student_id");
while($row = $my_signups_temp->fetch_assoc()) {
  $signed_ids[] = $row['tutorial_id'];
}

$stats = $conn->query("
  SELECT 
    COUNT(*) as total_enrolled,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_count
  FROM tutorial_signups
  WHERE student_id = $student_id
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0097e6, #00a8ff, #273c75);
    min-height: 100vh;
    padding: 20px;
    color: #2f3640;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    color: #fff;
    flex-wrap: wrap;
    gap: 15px;
  }

  .header h2 {
    margin: 0;
  }

  .header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .btn-print {
    background: #8e44ad;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
  }

  .btn-print:hover {
    background: #9b59b6;
  }

  .btn-logout {
    background: #e84118;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
  }

  .btn-logout:hover {
    background: #c23616;
  }

  .card {
    background: #ffffff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 1400px;
    margin: 0 auto 20px;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
  }

  .stat-card.green {
    background: linear-gradient(135deg, #44bd32 0%, #4cd137 100%);
  }

  .stat-card.orange {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
  }

  .stat-card.blue {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
  }

  .stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
  }

  .message {
    background: #dfe4ea;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    text-align: center;
  }

  .section-title {
    margin-bottom: 20px;
    color: #0097e6;
    border-bottom: 2px solid #0097e6;
    padding-bottom: 10px;
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

  .action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    color: white;
    transition: 0.3s;
    display: inline-block;
    border: none;
    cursor: pointer;
  }

  .join-btn {
    background: #44bd32;
  }

  .join-btn:hover {
    background: #4cd137;
  }

  .withdraw-btn {
    background: #e84118;
  }

  .withdraw-btn:hover {
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

  @media print {
    body {
      background: white;
    }

    .header-actions, .no-print, .message {
      display: none !important;
    }

    .stat-card {
      border: 2px solid #0097e6;
      color: #2f3640;
      background: white !important;
    }
  }
</style>
</head>
<body>

<div class="header">
  <h2>Welcome, <?php echo htmlspecialchars($student_name); ?>! 🎓</h2>
  <div class="header-actions">
    <button onclick="window.print()" class="btn-print no-print">🖨️ Print</button>
    <a class="btn-logout" href="logout.php">Logout</a>
  </div>
</div>

<div class="card">
  <?php if ($message): ?>
    <div class="message no-print"><?php echo $message; ?></div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="stat-number"><?php echo $stats['total_enrolled']; ?></div>
      <div class="stat-label">Total Enrollments</div>
    </div>
    <div class="stat-card green">
      <div class="stat-number"><?php echo $stats['approved_count']; ?></div>
      <div class="stat-label">Approved Tutorials</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
      <div class="stat-label">Pending Approvals</div>
    </div>
  </div>
</div>

<?php if ($my_signups->num_rows > 0): ?>
<div class="card">
  <h3 class="section-title">My Enrollments</h3>
  <table>
    <thead>
      <tr>
        <th>Tutorial</th>
        <th>Topic</th>
        <th>Mentor</th>
        <th>Schedule</th>
        <th>Duration</th>
        <th>Status</th>
        <th class="no-print">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($enroll = $my_signups->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($enroll['title']); ?></td>
        <td><?php echo htmlspecialchars($enroll['topic']); ?></td>
        <td><?php echo htmlspecialchars($enroll['mentor_name']); ?></td>
        <td><?php echo date('M d, Y g:i A', strtotime($enroll['schedule'])); ?></td>
        <td><?php echo htmlspecialchars($enroll['duration']); ?></td>
        <td>
          <span class="status-badge status-<?php echo $enroll['status']; ?>">
            <?php echo ucfirst($enroll['status']); ?>
          </span>
        </td>
        <td class="no-print">
          <a class="action-btn withdraw-btn" 
             href="?withdraw=<?php echo $enroll['tutorial_id']; ?>" 
             onclick="return confirm('Are you sure you want to withdraw? The mentor will be notified.')">
            Withdraw
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="card">
  <h3 class="section-title">Available Tutorials</h3>
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Topic</th>
        <th>Mentor</th>
        <th>Schedule</th>
        <th>Duration</th>
        <th>Enrolled</th>
        <th class="no-print">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $tutorials->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['title']); ?></td>
        <td><?php echo htmlspecialchars($row['topic']); ?></td>
        <td><?php echo htmlspecialchars($row['mentor_name']); ?></td>
        <td><?php echo date('M d, Y g:i A', strtotime($row['schedule'])); ?></td>
        <td><?php echo htmlspecialchars($row['duration']); ?></td>
        <td><?php echo $row['enrolled_count']; ?> students</td>
        <td class="no-print">
          <?php if (in_array($row['id'], $signed_ids)): ?>
            <span class="status-badge status-pending">Enrolled</span>
          <?php else: ?>
            <a class="action-btn join-btn" href="?join=<?php echo $row['id']; ?>">Join</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>