<?php
session_start();
require 'db.php';

// ป้องกันคนนอกยิง API เข้ามาตรงๆ
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized: ยังไม่ได้เข้าสู่ระบบ";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];

    $allowed_statuses = ['pending', 'member', 'no_member'];
    
    if (in_array($status, $allowed_statuses)) {
        $sql = "UPDATE users SET membership_status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $user_id);
        
        if ($stmt->execute()) {
            echo "Success";
        } else {
            http_response_code(500);
            echo "Database Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo "Invalid status";
    }
} else {
    http_response_code(400);
    echo "Missing parameters";
}
$conn->close();
?>