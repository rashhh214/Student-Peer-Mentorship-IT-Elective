<?php
/**
 * Email Configuration for Student Peer Mentorship System
 * Using PHPMailer with Gmail SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

/**
 * Send email using PHPMailer
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @return bool|string Returns true on success, error message on failure
 */
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        //========================================
        // SMTP Configuration
        //========================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // Gmail Credentials - REPLACE WITH YOUR OWN
        $mail->Username   = 'rasheedomar194@gmail.com';           
        $mail->Password   = 'sqqq kewr untu dtvv';            
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Debug Settings (0 = off, 1 = client messages, 2 = client and server messages)
        $mail->SMTPDebug  = 0;                               
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug ($level): $str");
        };

        //========================================
        // Email Headers
        //========================================
        $mail->setFrom('rasheedomar194@gmail.com', 'Student Peer Mentorship System');
        $mail->addAddress($to);
        $mail->addReplyTo('rasheedomar194@gmail.com', 'Student Peer Mentorship System');

        //========================================
        // Email Content
        //========================================
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);  // Plain text version

        //========================================
        // Send Email
        //========================================
        $mail->send();
        
        // Log success
        error_log("[EMAIL SUCCESS] Sent to: $to | Subject: $subject");
        return true;

    } catch (Exception $e) {
        // Log error details
        $error_msg = "Email Error: {$mail->ErrorInfo}";
        error_log("[EMAIL FAILED] To: $to | Subject: $subject | Error: $error_msg");
        
        // Return error message
        return $error_msg;
    }
}

