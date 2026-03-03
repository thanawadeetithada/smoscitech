<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activity_id'])) {
    
    $activity_id = intval($_POST['activity_id']);

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $hours_count = intval($_POST['hours_count']);
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $allowed_year_level = isset($_POST['target_year_level']) ? implode(',', $_POST['target_year_level']) : NULL;
    $allowed_academic_year = isset($_POST['target_academic_year']) ? implode(',', $_POST['target_academic_year']) : NULL;
    $allowed_department = isset($_POST['target_department']) ? implode(',', $_POST['target_department']) : NULL;

    $cover_update_query = "";
    $cover_image_name = null;

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $file_name = $_FILES['cover_image']['name'];
        $file_tmp = $_FILES['cover_image']['tmp_name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed_ext)) {
            if (!is_dir('uploads/covers')) {
                mkdir('uploads/covers', 0777, true);
            }

            $sql_old_img = "SELECT cover_image FROM activities WHERE activity_id = ?";
            $stmt_old = $conn->prepare($sql_old_img);
            $stmt_old->bind_param("i", $activity_id);
            $stmt_old->execute();
            $result_old = $stmt_old->get_result();
            if ($row = $result_old->fetch_assoc()) {
                $old_image = $row['cover_image'];
                if (!empty($old_image) && file_exists("uploads/covers/" . $old_image)) {
                    unlink("uploads/covers/" . $old_image);
                }
            }
            $stmt_old->close();

            $new_file_name = 'cover_' . time() . '_' . uniqid() . '.' . $ext;
            $upload_path = 'uploads/covers/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $cover_update_query = ", cover_image = ?";
                $cover_image_name = $new_file_name;
            }
        }
    }

    $sql_update_activity = "UPDATE activities SET 
                            title = ?, 
                            description = ?, 
                            location = ?, 
                            start_date = ?, 
                            end_date = ?, 
                            hours_count = ?, 
                            status = ?,
                            allowed_year_level = ?,
                            allowed_academic_year = ?,
                            allowed_department = ?
                            $cover_update_query 
                            WHERE activity_id = ?";

    $stmt = $conn->prepare($sql_update_activity);

    if ($cover_image_name) {
        $stmt->bind_param("sssssisssssi", 
            $title, $description, $location, $start_date, $end_date, $hours_count, $status, 
            $allowed_year_level, $allowed_academic_year, $allowed_department, 
            $cover_image_name, $activity_id
        );
    } else {
        $stmt->bind_param("sssssissssi", 
            $title, $description, $location, $start_date, $end_date, $hours_count, $status, 
            $allowed_year_level, $allowed_academic_year, $allowed_department, 
            $activity_id
        );
    }
    
    $stmt->execute();
    $stmt->close();

    $task_ids = isset($_POST['task_id']) ? $_POST['task_id'] : [];
    $task_names = isset($_POST['task_name']) ? $_POST['task_name'] : [];
    $task_capacities = isset($_POST['task_capacity']) ? $_POST['task_capacity'] : [];
    $task_details = isset($_POST['task_detail']) ? $_POST['task_detail'] : [];

    $keep_task_ids = [];

    for ($i = 0; $i < count($task_names); $i++) {
        $t_name = trim($task_names[$i]);
        $t_cap = intval($task_capacities[$i]);
        $t_detail = trim($task_details[$i]);
        
        if (empty($t_name)) continue;

        if (isset($task_ids[$i]) && !empty($task_ids[$i])) {
            $t_id = intval($task_ids[$i]);
            $keep_task_ids[] = $t_id;
            
            $sql_update_task = "UPDATE activity_tasks SET task_name=?, task_detail=?, capacity=? WHERE task_id=? AND activity_id=?";
            $stmt_task = $conn->prepare($sql_update_task);
            $stmt_task->bind_param("ssiii", $t_name, $t_detail, $t_cap, $t_id, $activity_id);
            $stmt_task->execute();
            $stmt_task->close();
            
        } else {
            $sql_insert_task = "INSERT INTO activity_tasks (activity_id, task_name, task_detail, capacity) VALUES (?, ?, ?, ?)";
            $stmt_task = $conn->prepare($sql_insert_task);
            $stmt_task->bind_param("issi", $activity_id, $t_name, $t_detail, $t_cap);
            $stmt_task->execute();
            $keep_task_ids[] = $stmt_task->insert_id;
            $stmt_task->close();
        }
    }

    if (count($keep_task_ids) > 0) {
        $ids_str = implode(",", $keep_task_ids);
        $sql_delete_tasks = "DELETE FROM activity_tasks WHERE activity_id = $activity_id AND task_id NOT IN ($ids_str)";
        $conn->query($sql_delete_tasks);
    } else {
        $conn->query("DELETE FROM activity_tasks WHERE activity_id = $activity_id");
    }

    $_SESSION['status_modal'] = [
        'type' => 'success',
        'title' => 'สำเร็จ',
        'message' => 'อัปเดตข้อมูลกิจกรรมเรียบร้อยแล้ว'
    ];
    
    header("Location: admin_activity.php");
    exit();

} else {
    header("Location: admin_activity.php");
    exit();
}
?>