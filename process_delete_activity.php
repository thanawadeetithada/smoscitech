<?php
session_start();
include 'db.php';

// 1. ตรวจสอบสิทธิ์การเข้าถึง (เหมือนกับหน้าหลัก)
$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// 2. รับ ID กิจกรรมและตรวจสอบความถูกต้อง
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activity_id <= 0) {
    die("ID กิจกรรมไม่ถูกต้อง");
}

// เริ่ม Transaction เพื่อป้องกันข้อมูลค้างหากลบไม่สำเร็จทุก Table
$conn->begin_transaction();

try {
    // 3. ลบข้อมูลในตารางที่เกี่ยวข้องก่อน (Child Tables) 
    // หมายเหตุ: หากคุณตั้งค่า Foreign Key เป็น ON DELETE CASCADE ไว้ใน DB ขั้นตอนนี้อาจไม่จำเป็น
    // แต่การเขียน Code ลบเองจะช่วยให้ควบคุม Flow ได้ชัวร์กว่า
    
    // ลบรายการลงทะเบียน
    $sql_reg = "DELETE FROM activity_registrations WHERE activity_id = ?";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->bind_param("i", $activity_id);
    $stmt_reg->execute();

    // ลบฝ่ายงาน/หน้าที่ (tasks)
    $sql_tasks = "DELETE FROM activity_tasks WHERE activity_id = ?";
    $stmt_tasks = $conn->prepare($sql_tasks);
    $stmt_tasks->bind_param("i", $activity_id);
    $stmt_tasks->execute();

    // 4. ลบตัวกิจกรรมหลัก
    $sql_act = "DELETE FROM activities WHERE activity_id = ?";
    $stmt_act = $conn->prepare($sql_act);
    $stmt_act->bind_param("i", $activity_id);
    $stmt_act->execute();

    // ยืนยันการลบทั้งหมด
    $conn->commit();

    // 5. ส่งกลับไปยังหน้าจัดการกิจกรรมพร้อมข้อความสำเร็จ
    // (ใช้ Alert ในหน้าหลักเพื่อแจ้งผล)
    header("Location: admin_activity.php?delete_success=1");
    exit();

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ยกเลิกการลบทั้งหมด (Rollback)
    $conn->rollback();
    echo "เกิดข้อผิดพลาดในการลบกิจกรรม: " . $e->getMessage();
}

$conn->close();
?>