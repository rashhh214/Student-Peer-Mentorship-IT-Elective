<?php
// mentor_notification_functions.php
// Include this file wherever you need to create notifications

/**
 * Create a notification for mentor
 */
function createMentorNotification($mentor_id, $tutorial_id, $type, $title, $message, $student_name = null) {
    global $conn;
    
    $sql = "INSERT INTO mentor_notifications (mentor_id, tutorial_id, type, title, message, student_name, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $mentor_id, $tutorial_id, $type, $title, $message, $student_name);
    
    return $stmt->execute();
}

/**
 * Notify mentor when student signs up
 */
function notifyStudentSignup($mentor_id, $tutorial_id, $student_name, $tutorial_title) {
    $title = "New Student Registration! 🎓";
    $message = "$student_name has registered for your tutorial '$tutorial_title'. Please review and approve their registration.";
    
    return createMentorNotification($mentor_id, $tutorial_id, 'student_signup', $title, $message, $student_name);
}

/**
 * Notify mentor when student withdraws
 */
function notifyStudentWithdrawal($mentor_id, $tutorial_id, $student_name, $tutorial_title) {
    $title = "Student Withdrawal";
    $message = "$student_name has withdrawn from your tutorial '$tutorial_title'.";
    
    return createMentorNotification($mentor_id, $tutorial_id, 'student_withdrawal', $title, $message, $student_name);
}

/**
 * Notify mentor when tutorial reaches capacity
 */
function notifyTutorialFull($mentor_id, $tutorial_id, $tutorial_title, $student_count) {
    $title = "Tutorial at Capacity! 📊";
    $message = "Your tutorial '$tutorial_title' now has $student_count approved students.";
    
    return createMentorNotification($mentor_id, $tutorial_id, 'tutorial_full', $title, $message);
}

/**
 * Remind mentor about upcoming tutorial
 */
function notifyTutorialReminder($mentor_id, $tutorial_id, $tutorial_title, $schedule_date) {
    $title = "Upcoming Tutorial Reminder ⏰";
    $message = "Your tutorial '$tutorial_title' is scheduled for $schedule_date. Make sure you're prepared!";
    
    return createMentorNotification($mentor_id, $tutorial_id, 'tutorial_reminder', $title, $message);
}

/**
 * Send system update notification
 */
function notifySystemUpdate($mentor_id, $title, $message) {
    global $conn;
    
    $sql = "INSERT INTO mentor_notifications (mentor_id, tutorial_id, type, title, message, created_at) 
            VALUES (?, NULL, 'system_update', ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $mentor_id, $title, $message);
    
    return $stmt->execute();
}

/**
 * Get unread notification count for mentor
 */
function getMentorUnreadCount($mentor_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM mentor_notifications 
            WHERE mentor_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Mark notification as read
 */
function markNotificationRead($notification_id, $mentor_id) {
    global $conn;
    
    $sql = "UPDATE mentor_notifications SET is_read = 1 
            WHERE id = ? AND mentor_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $mentor_id);
    
    return $stmt->execute();
}

/**
 * Mark all notifications as read for mentor
 */
function markAllNotificationsRead($mentor_id) {
    global $conn;
    
    $sql = "UPDATE mentor_notifications SET is_read = 1 
            WHERE mentor_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mentor_id);
    
    return $stmt->execute();
}

/**
 * Delete old notifications (30+ days)
 */
function cleanOldNotifications() {
    global $conn;
    
    $sql = "DELETE FROM mentor_notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_read = 1";
    
    return $conn->query($sql);
}

/**
 * Get notification statistics for mentor
 */
function getMentorNotificationStats($mentor_id) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN type = 'student_signup' THEN 1 ELSE 0 END) as signups,
                SUM(CASE WHEN type = 'student_withdrawal' THEN 1 ELSE 0 END) as withdrawals
            FROM mentor_notifications 
            WHERE mentor_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}
?>