<?php
require 'db.php';
session_start();

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    http_response_code(403);
    echo "คุณไม่มีสิทธิ์ลบข้อมูล";
    exit();
}

if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "ลบข้อมูลเรียบร้อยแล้ว";
    } else {
        echo "เกิดข้อผิดพลาดในการลบข้อมูล";
    }

    $stmt->close();
} else {
    echo "ไม่พบข้อมูลผู้ใช้";
}

$conn->close();
?>
