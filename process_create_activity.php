<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    die("Permission denied.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $hours_count = $_POST['hours_count'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $created_by = $_SESSION['user_id'];

    $target_dir = "uploads/covers/";
    $image_name = NULL;

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $image_name = "cover_" . time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $image_name;
        
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file);
    }

    $conn->begin_transaction();

    try {
        $sql_activity = "INSERT INTO activities (title, description, location, start_date, end_date, hours_count, cover_image, status, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?)";
        
        $stmt = $conn->prepare($sql_activity);
        $stmt->bind_param("sssssisi", $title, $description, $location, $start_date, $end_date, $hours_count, $image_name, $created_by);
        $stmt->execute();
        
        $activity_id = $conn->insert_id;

        if (isset($_POST['task_name']) && is_array($_POST['task_name'])) {
            $task_names = $_POST['task_name'];
            $task_capacities = $_POST['task_capacity'];
            $task_details = $_POST['task_detail'];

            $sql_task = "INSERT INTO activity_tasks (activity_id, task_name, task_detail, capacity) VALUES (?, ?, ?, ?)";
            $stmt_task = $conn->prepare($sql_task);

            foreach ($task_names as $index => $name) {
                if (!empty(trim($name))) {
                    $detail = $task_details[$index];
                    $capacity = (int)$task_capacities[$index];
                    $stmt_task->bind_param("issi", $activity_id, $name, $detail, $capacity);
                    $stmt_task->execute();
                }
            }
        }

        $conn->commit();

        $_SESSION['status_modal'] = [
            'type' => 'success',
            'title' => 'สร้างกิจกรรมสำเร็จ!',
            'message' => 'เพิ่มกิจกรรม "' . $title . '" เข้าสู่ระบบเรียบร้อยแล้ว'
        ];

    } catch (Exception $e) {
        $conn->rollback();

        if ($image_name && file_exists($target_dir . $image_name)) {
            unlink($target_dir . $image_name);
        }
        $_SESSION['status_modal'] = [
            'type' => 'danger',
            'title' => 'เกิดข้อผิดพลาด!',
            'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()
        ];
    }

    if (isset($stmt)) $stmt->close();
    $conn->close();
    
    header("Location: admin_activity.php");
    exit();

} else {
    header("Location: admin_activity.php");
    exit();
}
?>