<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_peer_mentorship";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$totalMentors = $conn->query("SELECT COUNT(*) AS total FROM mentors")->fetch_assoc()['total'];
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$totalTutorials = $conn->query("SELECT COUNT(*) AS total FROM tutorials")->fetch_assoc()['total'];
$totalSignups = $conn->query("SELECT COUNT(*) AS total FROM tutorial_signups")->fetch_assoc()['total'];


$tutorials = $conn->query("
    SELECT t.id, t.title, t.topic, t.schedule, t.duration,
           m.name AS mentor_name, t.is_public
    FROM tutorials t
    JOIN mentors m ON t.mentor_id = m.id
    ORDER BY t.id DESC

");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Peer Mentorship Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        header {
            background: #2f3640;
            color: white;
            text-align: center;
            padding: 20px 0;
        }
        .kpi-container {
            display: flex;
            justify-content: space-around;
            margin: 20px;
            flex-wrap: wrap;
        }
        .kpi-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            text-align: center;
            padding: 25px;
            margin: 10px;
            flex: 1 1 200px;
            transition: transform 0.2s ease;
        }
        .kpi-box:hover {
            transform: translateY(-5px);
        }
        .kpi-box h2 {
            font-size: 2rem;
            color: #0097e6;
            margin: 10px 0;
        }
        .report-section {
            margin: 30px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background: #273c75;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .print-btn {
            background: #44bd32;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .print-btn:hover {
            background: #4cd137;
        }

        @media print {
            .print-btn, header {
                display: none;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Student Peer Mentorship Dashboard</h1>
</header>

<section class="kpi-container">
    <div class="kpi-box">
        <p><strong>Total Mentors</strong></p>
        <h2><?php echo $totalMentors; ?></h2>
    </div>
    <div class="kpi-box">
        <p><strong>Total Students</strong></p>
        <h2><?php echo $totalStudents; ?></h2>
    </div>
    <div class="kpi-box">
        <p><strong>Total Tutorials</strong></p>
        <h2><?php echo $totalTutorials; ?></h2>
    </div>
    <div class="kpi-box">
        <p><strong>Total Signups</strong></p>
        <h2><?php echo $totalSignups; ?></h2>
    </div>
</section>

<section class="report-section">
    <h2>Tutorial List Report</h2>
    <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>

    <table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Topic</th>
            <th>Mentor</th>
            <th>Schedule</th>
            <th>Duration</th>
            <th>Visibility</th>
        </tr>
        <?php while ($row = $tutorials->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['title']); ?></td>
                <td><?= htmlspecialchars($row['topic']); ?></td>
                <td><?= htmlspecialchars($row['mentor_name']); ?></td>
                <td><?= htmlspecialchars($row['schedule']); ?></td>
                <td><?= htmlspecialchars($row['duration']); ?></td>
                <td><?= $row['is_public'] ? 'Public' : 'Private'; ?></td>
            </tr>
        <?php } ?>
    </table>
</section>

</body>
</html>
