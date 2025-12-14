<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPMailer Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-top: 0;
            text-align: center;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .test-form {
            margin: 30px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        button:active {
            transform: translateY(0);
        }
        .checklist {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .checklist h3 {
            margin-top: 0;
            color: #333;
        }
        .checklist ul {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        .checklist li:before {
            content: "□";
            position: absolute;
            left: 0;
            font-size: 20px;
            color: #667eea;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📧 Email Configuration Test</h1>

    <?php

    $phpmailer_files = [
        'Exception.php' => __DIR__ . '/phpmailer/Exception.php',
        'PHPMailer.php' => __DIR__ . '/phpmailer/PHPMailer.php',
        'SMTP.php' => __DIR__ . '/phpmailer/SMTP.php'
    ];

    $all_files_exist = true;
    foreach ($phpmailer_files as $name => $path) {
        if (!file_exists($path)) {
            $all_files_exist = false;
            echo "<div class='status error'>";
            echo "❌ Missing file: <code>phpmailer/$name</code>";
            echo "</div>";
        }
    }

    if ($all_files_exist) {
        echo "<div class='status success'>";
        echo "✅ All PHPMailer files found!";
        echo "</div>";
    }

    
    if (!file_exists(__DIR__ . '/email_config.php')) {
        echo "<div class='status error'>";
        echo "❌ Missing: <code>email_config.php</code>";
        echo "</div>";
    } else {
        echo "<div class='status success'>";
        echo "✅ email_config.php found!";
        echo "</div>";
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
        $test_email = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
        
        if (!$test_email) {
            echo "<div class='status error'>";
            echo "❌ Invalid email address!";
            echo "</div>";
        } else {
            echo "<div class='status info'>";
            echo "📤 Sending test email to: <strong>$test_email</strong><br>";
            echo "Please wait...";
            echo "</div>";

           
            require __DIR__ . '/email_config.php';
            
            $subject = "Test Email from Student Peer Mentorship System";
            $body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;'>
                <div style='background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #667eea; margin-top: 0;'>✅ PHPMailer Test Successful!</h2>
                    <p style='font-size: 16px; line-height: 1.6; color: #333;'>
                        Congratulations! Your PHPMailer configuration is working correctly.
                    </p>
                    <div style='background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                        <p style='margin: 5px 0;'><strong>Recipient:</strong> $test_email</p>
                        <p style='margin: 5px 0;'><strong>System:</strong> Student Peer Mentorship</p>
                    </div>
                    <p style='color: #666; font-size: 14px;'>
                        Your system is now ready to send:
                    </p>
                    <ul style='color: #666; font-size: 14px;'>
                        <li>Account verification emails</li>
                        <li>Tutorial approval notifications</li>
                        <li>Tutorial rejection notifications</li>
                    </ul>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>
                        This is an automated test email from your Student Peer Mentorship System
                    </p>
                </div>
            </div>
            ";

            $result = sendMail($test_email, $subject, $body);
            
            if ($result === true) {
                echo "<div class='status success'>";
                echo "<h3 style='margin-top:0;'>✅ Email Sent Successfully!</h3>";
                echo "<p>Check your inbox (and spam folder) for the test email.</p>";
                echo "<p><strong>What to do next:</strong></p>";
                echo "<ol>";
                echo "<li>Check your email inbox</li>";
                echo "<li>If not in inbox, check spam/junk folder</li>";
                echo "<li>If email arrives, your setup is complete!</li>";
                echo "<li>If no email after 2 minutes, check troubleshooting below</li>";
                echo "</ol>";
                echo "</div>";
            } else {
                echo "<div class='status error'>";
                echo "<h3 style='margin-top:0;'>❌ Email Failed to Send</h3>";
                echo "<p><strong>Error:</strong> $result</p>";
                echo "<p><strong>Check:</strong></p>";
                echo "<ul>";
                echo "<li>Gmail credentials in email_config.php</li>";
                echo "<li>App Password is correct (16 characters)</li>";
                echo "<li>2-Step Verification is enabled on Gmail</li>";
                echo "<li>OpenSSL extension is enabled in PHP</li>";
                echo "</ul>";
                echo "</div>";
            }
        }
    }
    ?>

    <div class="test-form">
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Enter your email to receive test:</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    placeholder="your-email@gmail.com" 
                    required
                >
            </div>
            <button type="submit">🚀 Send Test Email</button>
        </form>
    </div>

    <div class="checklist">
        <h3>📋 Setup Checklist</h3>
        <ul>
            <li>Enable 2-Step Verification on Gmail</li>
            <li>Generate App Password from Google Account</li>
            <li>Create phpmailer folder with 3 files</li>
            <li>Update email_config.php with credentials</li>
            <li>Test email sends successfully</li>
            <li>Email arrives in inbox</li>
        </ul>
    </div>

    <div class="status warning">
        <strong>⚠️ Important Notes:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Use App Password, NOT your regular Gmail password</li>
            <li>App Password is 16 characters (e.g., <code>abcd efgh ijkl mnop</code>)</li>
            <li>First email may take 1-2 minutes to arrive</li>
            <li>Check spam folder if not in inbox</li>
        </ul>
    </div>

</div>

</body>
</html>