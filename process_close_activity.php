<?php
session_start();
include 'db.php';

// เช็กสิทธิ์แอดมิน...
$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $activity_id = intval($_GET['id']);
    
    // อัปเดตสถานะจาก open เป็น closed
    $sql = "UPDATE activities SET status = 'closed' WHERE activity_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $stmt->close();
}

// อัปเดตเสร็จแล้วเด้งกลับไปหน้าจัดการกิจกรรมเดิม
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>