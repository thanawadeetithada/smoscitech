<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $activity_id = intval($_GET['id']);
    
    $sql = "UPDATE activities SET status = 'closed' WHERE activity_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>