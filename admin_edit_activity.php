<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$can_edit = ($_SESSION['userrole'] === 'club_president');
$disabled = $can_edit ? '' : 'disabled';

$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM activities WHERE activity_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();

if (!$activity) {
    die("ไม่พบข้อมูลกิจกรรม");
}

$sql_tasks = "SELECT * FROM activity_tasks WHERE activity_id = ?";
$stmt_tasks = $conn->prepare($sql_tasks);
$stmt_tasks->bind_param("i", $activity_id);
$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();

$academic_years = [];
$sql_years = "SELECT DISTINCT academic_year FROM users WHERE academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC";
$result_years = $conn->query($sql_years);
if ($result_years && $result_years->num_rows > 0) {
    while ($row = $result_years->fetch_assoc()) {
        $academic_years[] = $row['academic_year'];
    }
}

$year_levels = ["ชั้นปีที่ 1", "ชั้นปีที่ 2", "ชั้นปีที่ 3", "ชั้นปีที่ 4"];
$depts = [
    "วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", 
    "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", 
    "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"
];

$current_year_levels = !empty($activity['allowed_year_level']) ? explode(',', $activity['allowed_year_level']) : [];
$current_academic_years = !empty($activity['allowed_academic_year']) ? explode(',', $activity['allowed_academic_year']) : [];
$current_departments = !empty($activity['allowed_department']) ? explode(',', $activity['allowed_department']) : [];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $can_edit ? 'แก้ไขกิจกรรม' : 'รายละเอียดกิจกรรม'; ?>:
        <?php echo htmlspecialchars($activity['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fc;
    }

    .nav-item a {
        color: white;
        margin-right: 1rem;
    }

    .navbar {
        padding: 20px;
    }

    .nav-link:hover {
        color: white;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .form-label {
        font-weight: 600;
        color: #4e73df;
    }

    .task-row {
        background: #f1f3f9;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        position: relative;
    }

    .btn-remove-task {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
    }

    .current-cover {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 10px;
    }

    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link text-white" href="logout.php">
                    <i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">รายการ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-unstyled">
                <li><a href="admin_report_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="admin_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li>
                    <a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2">
                        <i class="fa-regular fa-address-book"></i>
                        <?php echo (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'executive') ? 'E-Portfolio' : 'E-Portfolio / Transcript'; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <li>
                    <a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2">
                        <i class="fa-regular fa-star"></i> คะแนนกิจกรรม
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], ['academic_officer', 'club_president'])): ?>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex align-items-center mb-4">
                    <a href="admin_activity.php" class="btn me-3"><i class="fas fa-arrow-left"></i></a>
                    <h3 class="fw-bold mb-0"><?php echo $can_edit ? 'แก้ไขข้อมูลกิจกรรม' : 'รายละเอียดกิจกรรม'; ?></h3>
                </div>

                <form action="process_edit_activity.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 border-bottom pb-2"><i
                                    class="fas fa-info-circle me-2 text-primary"></i>ข้อมูลทั่วไป</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">รูปหน้าปกปัจจุบัน</label>
                                    <?php if($activity['cover_image']): ?>
                                    <img src="uploads/covers/<?php echo $activity['cover_image']; ?>"
                                        class="current-cover d-block shadow-sm">
                                    <?php else: ?>
                                    <div class="p-3 bg-light text-muted border rounded mb-2">ยังไม่มีรูปหน้าปก</div>
                                    <?php endif; ?>

                                    <?php if($can_edit): ?>
                                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                                    <small class="text-muted italic">* อัปโหลดใหม่เพื่อเปลี่ยนรูปเดิม</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ชื่อกิจกรรม</label>
                                    <input type="text" name="title" class="form-control"
                                        value="<?php echo htmlspecialchars($activity['title']); ?>" required
                                        <?php echo $disabled; ?>>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">รายละเอียดกิจกรรม</label>
                                    <textarea name="description" class="form-control" rows="4"
                                        <?php echo $disabled; ?>><?php echo htmlspecialchars($activity['description']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">สถานที่จัดกิจกรรม</label>
                                    <input type="text" name="location" class="form-control"
                                        value="<?php echo htmlspecialchars($activity['location']); ?>"
                                        <?php echo $disabled; ?>>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">จำนวนชั่วโมงกิจกรรม (กยศ.)</label>
                                    <input type="number" name="hours_count" class="form-control"
                                        value="<?php echo $activity['hours_count']; ?>" <?php echo $disabled; ?>>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">สถานะ</label>
                                    <select name="status" class="form-select" <?php echo $disabled; ?>>
                                        <option value="open"
                                            <?php if($activity['status'] == 'open') echo 'selected'; ?>>Open
                                            (เปิดรับสมัคร)</option>
                                        <option value="closed"
                                            <?php if($activity['status'] == 'closed') echo 'selected'; ?>>Closed
                                            (ปิดรับสมัคร)</option>
                                        <option value="completed"
                                            <?php if($activity['status'] == 'completed') echo 'selected'; ?>>Completed
                                            (จบกิจกรรม)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">วันเริ่มงาน</label>
                                    <input type="datetime-local" name="start_date" class="form-control"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($activity['start_date'])); ?>"
                                        required <?php echo $disabled; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">วันสิ้นสุด</label>
                                    <input type="datetime-local" name="end_date" class="form-control"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($activity['end_date'])); ?>"
                                        required <?php echo $disabled; ?>>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 border-primary">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4"><i
                                    class="fas fa-user-lock me-2 text-primary"></i>เงื่อนไขผู้มีสิทธิ์เข้าร่วม
                                (ปล่อยว่างหากเปิดรับทุกคน)</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">ชั้นปีที่รับสมัคร</label>
                                    <select name="target_year_level[]" class="form-select select2-multiple"
                                        multiple="multiple" data-placeholder="เลือกชั้นปี..." <?php echo $disabled; ?>>
                                        <?php foreach ($year_levels as $yl): ?>
                                        <option value="<?php echo htmlspecialchars($yl); ?>"
                                            <?php echo in_array($yl, $current_year_levels) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($yl); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">ปีการศึกษา</label>
                                    <select name="target_academic_year[]" class="form-select select2-multiple"
                                        multiple="multiple" data-placeholder="เลือกปีการศึกษา..."
                                        <?php echo $disabled; ?>>
                                        <?php foreach ($academic_years as $ay): ?>
                                        <option value="<?php echo htmlspecialchars($ay); ?>"
                                            <?php echo in_array($ay, $current_academic_years) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ay); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">สาขาวิชา</label>
                                    <select name="target_department[]" class="form-select select2-multiple"
                                        multiple="multiple" data-placeholder="เลือกสาขาวิชา..."
                                        <?php echo $disabled; ?>>
                                        <?php foreach ($depts as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>"
                                            <?php echo in_array($dept, $current_departments) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                <h5 class="card-title mb-0"><i
                                        class="fas fa-users me-2 text-success"></i>หน้าที่และฝ่ายงาน</h5>
                                <?php if($can_edit): ?>
                                <button type="button" class="btn btn-sm btn-success px-3" onclick="addTask()"><i
                                        class="fas fa-plus"></i> เพิ่มฝ่ายใหม่</button>
                                <?php endif; ?>
                            </div>

                            <div id="tasks-container">
                                <?php while($task = $tasks_result->fetch_assoc()): ?>
                                <div class="task-row animate__animated animate__fadeIn">
                                    <?php if($can_edit): ?>
                                    <button type="button" class="btn-remove-task"
                                        onclick="this.parentElement.remove()">&times;</button>
                                    <?php endif; ?>

                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="small fw-bold">ชื่อฝ่าย/หน้าที่</label>
                                            <input type="text" name="task_name[]" class="form-control form-control-sm"
                                                value="<?php echo htmlspecialchars($task['task_name']); ?>" required
                                                <?php echo $disabled; ?>>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small fw-bold">จำนวนรับ (คน)</label>
                                            <input type="number" name="task_capacity[]"
                                                class="form-control form-control-sm"
                                                value="<?php echo $task['capacity']; ?>" min="1"
                                                <?php echo $disabled; ?>>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold">รายละเอียด</label>
                                            <input type="text" name="task_detail[]" class="form-control form-control-sm"
                                                value="<?php echo htmlspecialchars($task['task_detail']); ?>"
                                                <?php echo $disabled; ?>>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <?php if($can_edit): ?>
                        <a href="admin_activity.php" class="btn btn-outline-secondary px-4 me-2">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary px-5 shadow">บันทึกการเปลี่ยนแปลง</button>
                        <?php else: ?>
                        <a href="admin_activity.php" class="btn btn-primary px-5 shadow">กลับหน้าหลัก</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.select2-multiple').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    });

    function addTask() {
        const container = document.getElementById('tasks-container');
        const div = document.createElement('div');
        div.className = 'task-row animate__animated animate__fadeIn';
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

</body>

</html>