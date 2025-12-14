<?php
include 'mentor_notification_functions.php';
session_start();
include __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['mentor_id'])) {
  header("Location: mentor_login.php");
  exit();
}

$mentor_id = $_SESSION['mentor_id'];
$mentor_name = $_SESSION['mentor_name'];

// Get mentor email
$mentor_result = $conn->query("SELECT email FROM mentors WHERE id=$mentor_id");
$mentor_data = $mentor_result->fetch_assoc();
$mentor_email = $mentor_data['email'];

// Mark notification as read if requested
if (isset($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $conn->query("UPDATE mentor_notifications SET is_read = 1 WHERE id = $notif_id AND mentor_id = $mentor_id");
    header("Location: mentor_dashboard.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE mentor_notifications SET is_read = 1 WHERE mentor_id = $mentor_id");
    header("Location: mentor_dashboard.php");
    exit();
}

// Get tutorials with student count
$tutorials = $conn->query("
  SELECT t.*, 
    (SELECT COUNT(*) FROM tutorial_signups WHERE tutorial_id = t.id) as total_students,
    (SELECT COUNT(*) FROM tutorial_signups WHERE tutorial_id = t.id AND status='pending') as pending_students,
    (SELECT COUNT(*) FROM tutorial_signups WHERE tutorial_id = t.id AND status='approved') as approved_students
  FROM tutorials t 
  WHERE mentor_id = $mentor_id
  ORDER BY t.schedule DESC
");

// Get all notifications for this mentor
$notifications = $conn->query("
  SELECT mn.*, t.title as tutorial_title
  FROM mentor_notifications mn
  LEFT JOIN tutorials t ON mn.tutorial_id = t.id
  WHERE mn.mentor_id = $mentor_id
  ORDER BY mn.created_at DESC
  LIMIT 20
");

$unread_count = $conn->query("
  SELECT COUNT(*) as count 
  FROM mentor_notifications 
  WHERE mentor_id = $mentor_id AND is_read = 0
")->fetch_assoc()['count'];

// Get statistics
$stats_query = $conn->query("
  SELECT 
    COUNT(DISTINCT ts.tutorial_id) as tutorials_with_students,
    SUM(CASE WHEN ts.status='pending' THEN 1 ELSE 0 END) as total_pending,
    SUM(CASE WHEN ts.status='approved' THEN 1 ELSE 0 END) as total_approved
  FROM tutorial_signups ts
  JOIN tutorials t ON ts.tutorial_id = t.id
  WHERE t.mentor_id = $mentor_id
");
$stats = $stats_query->fetch_assoc();
$total_tutorials = $tutorials->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mentor Dashboard</title>
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

  .notification-bell {
    position: relative;
    background: #f39c12;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }

  .notification-bell:hover {
    background: #e67e22;
  }

  .notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    min-width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 2px;
  }

  .header a, .header button {
    color: white;
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 6px;
    font-weight: 600;
    transition: 0.3s;
    border: none;
    cursor: pointer;
    font-size: 14px;
  }

  .btn-create {
    background: #44bd32;
  }

  .btn-create:hover {
    background: #4cd137;
  }

  .btn-print {
    background: #8e44ad;
  }

  .btn-print:hover {
    background: #9b59b6;
  }

  .btn-logout {
    background: #e84118;
  }

  .btn-logout:hover {
    background: #c23616;
  }

  .card {
    background: #ffffff;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 1200px;
    margin: auto;
    margin-bottom: 20px;
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

  /* Notification Panel Styles */
  .notification-panel {
    display: none;
    position: fixed;
    top: 80px;
    right: 20px;
    width: 400px;
    max-width: 90vw;
    max-height: 600px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    overflow: hidden;
    z-index: 1000;
    animation: slideIn 0.3s ease;
  }

  .notification-panel.active {
    display: block;
  }

  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .notification-header {
    background: linear-gradient(135deg, #0097e6, #00a8ff);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .notification-header h3 {
    margin: 0;
    font-size: 1.2rem;
  }

  .notification-actions {
    display: flex;
    gap: 10px;
  }

  .mark-all-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: 0.2s;
  }

  .mark-all-btn:hover {
    background: rgba(255,255,255,0.3);
  }

  .notification-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.8rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 30px;
    height: 30px;
  }

  .notification-list {
    max-height: 500px;
    overflow-y: auto;
  }

  .notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    gap: 15px;
    transition: 0.2s;
    cursor: pointer;
  }

  .notification-item:hover {
    background: #f8f9fa;
  }

  .notification-item.unread {
    background: #e8f4fd;
    border-left: 4px solid #0097e6;
  }

  .notification-icon {
    font-size: 2rem;
    flex-shrink: 0;
  }

  .notification-content {
    flex: 1;
  }

  .notification-type {
    font-size: 0.75rem;
    color: #7f8c8d;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .notification-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 0.95rem;
  }

  .notification-message {
    color: #7f8c8d;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 8px;
  }

  .notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
  }

  .notification-time {
    font-size: 0.75rem;
    color: #95a5a6;
  }

  .notification-tutorial {
    font-size: 0.75rem;
    color: #3498db;
    font-weight: 600;
  }

  .no-notifications {
    text-align: center;
    padding: 60px 20px;
    color: #95a5a6;
  }

  .no-notifications-icon {
    font-size: 4rem;
    margin-bottom: 15px;
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

  .view-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    color: white;
    background: #44bd32;
    transition: 0.3s;
    display: inline-block;
  }

  .view-btn:hover {
    background: #4cd137;
  }

  .no-tutorials {
    text-align: center;
    color: #718093;
    padding: 40px;
    font-size: 1.1rem;
  }

  /* Overlay for notification panel */
  .notification-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
    z-index: 999;
  }

  .notification-overlay.active {
    display: block;
  }

  /* Scrollbar styling */
  .notification-list::-webkit-scrollbar {
    width: 8px;
  }

  .notification-list::-webkit-scrollbar-track {
    background: #f1f1f1;
  }

  .notification-list::-webkit-scrollbar-thumb {
    background: #bdc3c7;
    border-radius: 4px;
  }

  .notification-list::-webkit-scrollbar-thumb:hover {
    background: #95a5a6;
  }

  /* Print Styles */
  @media print {
    body {
      background: white;
      padding: 20px;
    }

    .header-actions,
    .no-print,
    .notification-panel,
    .notification-overlay {
      display: none !important;
    }

    .header {
      color: #2f3640;
      border-bottom: 3px solid #0097e6;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }

    .card {
      box-shadow: none;
      page-break-inside: avoid;
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
  <h2>Welcome, <?php echo htmlspecialchars($mentor_name); ?>!</h2>
  <div class="header-actions">
    <button class="notification-bell" onclick="toggleNotifications()">
      🔔 Notifications
      <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?php echo $unread_count; ?></span>
      <?php endif; ?>
    </button>
    <a href="create_tutorial.php" class="btn-create">+ Create Tutorial</a>
    <button onclick="window.print()" class="btn-print no-print">🖨️ Print</button>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</div>

<!-- Notification Overlay -->
<div class="notification-overlay" id="notificationOverlay" onclick="toggleNotifications()"></div>

<!-- Notification Panel -->
<div class="notification-panel" id="notificationPanel">
  <div class="notification-header">
    <h3>📬 Notifications</h3>
    <div class="notification-actions">
      <?php if ($unread_count > 0): ?>
        <a href="?mark_all_read" class="mark-all-btn">Mark all read</a>
      <?php endif; ?>
      <button class="notification-close" onclick="toggleNotifications()">×</button>
    </div>
  </div>
  
  <div class="notification-list">
    <?php if ($notifications->num_rows > 0): ?>
      <?php while($notif = $notifications->fetch_assoc()): ?>
        <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>" 
             onclick="window.location.href='?mark_read=<?php echo $notif['id']; ?>'">
          <div class="notification-icon">
            <?php 
            $icons = [
              'student_signup' => '🎓',
              'student_withdrawal' => '👋',
              'tutorial_full' => '📊',
              'tutorial_reminder' => '⏰',
              'system_update' => '🔔'
            ];
            echo $icons[$notif['type']] ?? '📢';
            ?>
          </div>
          <div class="notification-content">
            <div class="notification-type"><?php echo str_replace('_', ' ', $notif['type']); ?></div>
            <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
            <div class="notification-meta">
              <div class="notification-time">
                <?php 
                $time_ago = time() - strtotime($notif['created_at']);
                if ($time_ago < 3600) {
                  echo floor($time_ago / 60) . ' min ago';
                } elseif ($time_ago < 86400) {
                  echo floor($time_ago / 3600) . ' hrs ago';
                } else {
                  echo floor($time_ago / 86400) . ' days ago';
                }
                ?>
              </div>
              <?php if ($notif['tutorial_title']): ?>
                <div class="notification-tutorial">
                  📚 <?php echo htmlspecialchars($notif['tutorial_title']); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-notifications">
        <div class="no-notifications-icon">🔕</div>
        <p>No notifications yet</p>
        <p style="font-size: 0.9rem; margin-top: 10px;">You'll be notified when students sign up for your tutorials</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="stats-grid">
    <div class="stat-card blue">
      <div class="stat-number"><?php echo $total_tutorials; ?></div>
      <div class="stat-label">Total Tutorials</div>
    </div>
    <div class="stat-card green">
      <div class="stat-number"><?php echo $stats['total_approved'] ?? 0; ?></div>
      <div class="stat-label">Approved Students</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-number"><?php echo $stats['total_pending'] ?? 0; ?></div>
      <div class="stat-label">Pending Approvals</div>
    </div>
  </div>

  <h3>Your Tutorials</h3>
  <?php 
  $tutorials->data_seek(0);
  if ($tutorials->num_rows > 0): 
  ?>
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Topic</th>
          <th>Schedule</th>
          <th>Duration</th>
          <th>Students</th>
          <th class="no-print">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $tutorials->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['topic']); ?></td>
            <td><?php echo date('M d, Y g:i A', strtotime($row['schedule'])); ?></td>
            <td><?php echo htmlspecialchars($row['duration']); ?></td>
            <td>
              <?php echo $row['approved_students']; ?> approved
              <?php if ($row['pending_students'] > 0): ?>
                <br><span style="color: #f39c12; font-weight: 600;">
                  <?php echo $row['pending_students']; ?> pending
                </span>
              <?php endif; ?>
            </td>
            <td class="no-print">
              <a class="view-btn" href="view_students.php?tutorial_id=<?php echo $row['id']; ?>">
                View Students
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-tutorials">
      You haven't created any tutorials yet. Click "Create Tutorial" to get started!
    </div>
  <?php endif; ?>
</div>

<script>
function toggleNotifications() {
  const panel = document.getElementById('notificationPanel');
  const overlay = document.getElementById('notificationOverlay');
  
  panel.classList.toggle('active');
  overlay.classList.toggle('active');
}

// Auto-refresh notification count every 30 seconds
setInterval(function() {
  fetch(window.location.href)
    .then(response => response.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newBadge = doc.querySelector('.notification-badge');
      const currentBadge = document.querySelector('.notification-badge');
      
      if (newBadge && currentBadge) {
        currentBadge.textContent = newBadge.textContent;
        currentBadge.style.display = newBadge.style.display;
      } else if (newBadge && !currentBadge) {
        // Badge appeared, reload to show it
        location.reload();
      } else if (!newBadge && currentBadge) {
        currentBadge.style.display = 'none';
      }
    });
}, 30000);
</script>

</body>
</html>