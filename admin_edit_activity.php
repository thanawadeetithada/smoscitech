<?php
session_start();
include 'db.php';

// 1. ตรวจสอบสิทธิ์
$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// 2. รับ ID และดึงข้อมูลกิจกรรมเดิม
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM activities WHERE activity_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    die("ไม่พบข้อมูลกิจกรรม");
}

// 3. ดึงข้อมูลหน้าที่/ฝ่ายงานเดิม
$sql_tasks = "SELECT * FROM activity_tasks WHERE activity_id = ?";
$stmt_tasks = $conn->prepare($sql_tasks);
$stmt_tasks->bind_param("i", $activity_id);
$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขกิจกรรม: <?php echo htmlspecialchars($activity['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fc; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-label { font-weight: 600; color: #4e73df; }
        .task-row { background: #f1f3f9; padding: 15px; border-radius: 10px; margin-bottom: 10px; position: relative; }
        .btn-remove-task { position: absolute; top: -10px; right: -10px; background: #dc3545; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
        .current-cover { width: 100%; max-height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center mb-4">
                <a href="admin_manage_activity.php?id=<?php echo $activity_id; ?>" class="btn btn-light me-3 shadow-sm"><i class="fas fa-arrow-left"></i></a>
                <h2 class="fw-bold mb-0">แก้ไขข้อมูลกิจกรรม</h2>
            </div>

            <form action="process_edit_activity.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4 border-bottom pb-2"><i class="fas fa-info-circle me-2 text-primary"></i>ข้อมูลทั่วไป</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">รูปหน้าปกปัจจุบัน</label>
                                <?php if($activity['cover_image']): ?>
                                    <img src="uploads/covers/<?php echo $activity['cover_image']; ?>" class="current-cover d-block shadow-sm">
                                <?php else: ?>
                                    <div class="p-3 bg-light text-muted border rounded mb-2">ยังไม่มีรูปหน้าปก</div>
                                <?php endif; ?>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                                <small class="text-muted italic">* อัปโหลดใหม่เพื่อเปลี่ยนรูปเดิม</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">ชื่อกิจกรรม</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($activity['title']); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($activity['description']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">สถานที่</label>
                                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($activity['location']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">จำนวนชั่วโมง (กยศ.)</label>
                                <input type="number" name="hours_count" class="form-control" value="<?php echo $activity['hours_count']; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">สถานะ</label>
                                <select name="status" class="form-select">
                                    <option value="open" <?php if($activity['status'] == 'open') echo 'selected'; ?>>Open (เปิดรับสมัคร)</option>
                                    <option value="closed" <?php if($activity['status'] == 'closed') echo 'selected'; ?>>Closed (ปิดรับสมัคร)</option>
                                    <option value="completed" <?php if($activity['status'] == 'completed') echo 'selected'; ?>>Completed (จบกิจกรรม)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">วันเริ่มงาน</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($activity['start_date'])); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">วันสิ้นสุด</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($activity['end_date'])); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                            <h5 class="card-title mb-0"><i class="fas fa-users me-2 text-success"></i>หน้าที่และฝ่ายงาน</h5>
                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3" onclick="addTask()"><i class="fas fa-plus"></i> เพิ่มฝ่ายใหม่</button>
                        </div>
                        
                        <div id="tasks-container">
                            <?php while($task = $tasks_result->fetch_assoc()): ?>
                            <div class="task-row animate__animated animate__fadeIn">
                                <button type="button" class="btn-remove-task" onclick="this.parentElement.remove()">&times;</button>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="small fw-bold">ชื่อฝ่าย/หน้าที่</label>
                                        <input type="text" name="task_name[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($task['task_name']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">จำนวนรับ (คน)</label>
                                        <input type="number" name="task_capacity[]" class="form-control form-control-sm" value="<?php echo $task['capacity']; ?>" min="1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">รายละเอียด</label>
                                        <input type="text" name="task_detail[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($task['task_detail']); ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <a href="admin_manage_activity.php?id=<?php echo $activity_id; ?>" class="btn btn-light px-4 me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary px-5 shadow">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addTask() {
    const container = document.getElementById('tasks-container');
    const div = document.createElement('div');
    div.className = 'task-row';
    div.innerHTML = `
        <button type="button" class="btn-remove-task" onclick="this.parentElement.remove()">&times;</button>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="small fw-bold">ชื่อฝ่าย/หน้าที่</label>
                <input type="text" name="task_name[]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold">จำนวนรับ (คน)</label>
                <input type="number" name="task_capacity[]" class="form-control form-control-sm" value="10" min="1">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold">รายละเอียด</label>
                <input type="text" name="task_detail[]" class="form-control form-control-sm">
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>