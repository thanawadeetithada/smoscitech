<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activity_id <= 0) {
    die("ID กิจกรรมไม่ถูกต้อง");
}

$conn->begin_transaction();

try {
    $sql_reg = "DELETE FROM activity_registrations WHERE activity_id = ?";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->bind_param("i", $activity_id);
    $stmt_reg->execute();
    $sql_tasks = "DELETE FROM activity_tasks WHERE activity_id = ?";
    $stmt_tasks = $conn->prepare($sql_tasks);
    $stmt_tasks->bind_param("i", $activity_id);
    $stmt_tasks->execute();
    $sql_act = "DELETE FROM activities WHERE activity_id = ?";
    $stmt_act = $conn->prepare($sql_act);
    $stmt_act->bind_param("i", $activity_id);
    $stmt_act->execute();

    $conn->commit();

    header("Location: admin_activity.php?delete_success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "เกิดข้อผิดพลาดในการลบกิจกรรม: " . $e->getMessage();
}

$conn->close();
?>