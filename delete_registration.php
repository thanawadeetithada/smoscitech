<?php
session_start();
include 'db.php';

// 1. ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registration_id'])) {
    $registration_id = intval($_POST['registration_id']);
    $user_id = $_SESSION['user_id'];

    // 2. ตรวจสอบเพื่อความปลอดภัยว่ารายการที่ลบเป็นของ User คนนี้จริงๆ
    // (ป้องกันกรณีคนแอบส่ง ID ของคนอื่นมาลบผ่าน Inspect Element)
    $check_sql = "SELECT registration_id FROM activity_registrations WHERE registration_id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $registration_id, $user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        // 3. เริ่มกระบวนการลบ
        $delete_sql = "DELETE FROM activity_registrations WHERE registration_id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("i", $registration_id);

        if ($stmt_delete->execute()) {
            // ตั้งค่า Session เพื่อให้หน้า activity.php แสดง Modal สำเร็จ
            $_SESSION['status_modal'] = [
                'type' => 'success',
                'title' => 'สำเร็จ!',
                'message' => 'ยกเลิกการลงทะเบียนกิจกรรมเรียบร้อยแล้ว'
            ];
        } else {
            $_SESSION['status_modal'] = [
                'type' => 'error',
                'title' => 'เกิดข้อผิดพลาด',
                'message' => 'ไม่สามารถลบข้อมูลได้ กรุณาลองใหม่อีกครั้ง'
            ];
        }
        $stmt_delete->close();
    } else {
        $_SESSION['status_modal'] = [
            'type' => 'error',
            'title' => 'ปฏิเสธคำขอ',
            'message' => 'คุณไม่มีสิทธิ์ลบรายการนี้'
        ];
    }
    $stmt_check->close();
}

// 4. ส่งกลับไปหน้าเดิม
header("Location: activity.php");
exit();
?>