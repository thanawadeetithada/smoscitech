<?php
session_start();
include 'db.php';

// สมมติว่าในหน้า Login มีการเก็บ $_SESSION['user_id'] ไว้แล้ว
// หากยังไม่มี ต้องจัดการให้ session มี user_id ของคนที่กำลังใช้งานอยู่
if (!isset($_SESSION['user_id'])) {
    // กำหนดค่าจำลองชั่วคราวเพื่อให้เทสระบบได้ (ลบออกเมื่อเชื่อมกับระบบ Login จริง)
    $_SESSION['user_id'] = 17; // อ้างอิงจาก DB ที่คุณให้มา (สมชาย)
}

$user_id = $_SESSION['user_id'];
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activity_id === 0) {
    header("Location: activity.php");
    exit();
}

// ==========================================
// 1. จัดการเมื่อกดปุ่ม "ยืนยันการเข้าร่วม"
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_activity'])) {
    $task_id = $_POST['task_id'];
    
    // บันทึกลงตาราง activity_registrations
    $insert_sql = "INSERT INTO activity_registrations (user_id, activity_id, task_id, registration_status, participation_status) 
                   VALUES (?, ?, ?, 'pending', 'waiting')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iii", $user_id, $activity_id, $task_id);
    
    if ($stmt->execute()) {
        $_SESSION['status_modal'] = [
            'type' => 'success',
            'title' => 'สำเร็จ',
            'message' => 'ลงทะเบียนเข้าร่วมกิจกรรมเรียบร้อยแล้ว รอการอนุมัติจากเจ้าหน้าที่'
        ];
    } else {
        $_SESSION['status_modal'] = [
            'type' => 'error',
            'title' => 'ผิดพลาด',
            'message' => 'ไม่สามารถลงทะเบียนได้ กรุณาลองใหม่อีกครั้ง'
        ];
    }
    $stmt->close();
    header("Location: participate_activity.php?id=" . $activity_id);
    exit();
}

// ==========================================
// 2. ดึงข้อมูลกิจกรรม
// ==========================================
$sql_act = "SELECT * FROM activities WHERE activity_id = ?";
$stmt = $conn->prepare($sql_act);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    echo "ไม่พบข้อมูลกิจกรรม";
    exit();
}

// ==========================================
// 3. ดึงข้อมูลฝ่าย/หน้าที่ (Tasks) และจำนวนคนที่สมัครไปแล้ว
// ==========================================
$tasks = [];
$sql_tasks = "SELECT t.*, 
              (SELECT COUNT(*) FROM activity_registrations r WHERE r.task_id = t.task_id AND r.registration_status != 'cancelled') as current_reg 
              FROM activity_tasks t 
              WHERE t.activity_id = ?";
$stmt = $conn->prepare($sql_tasks);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result_tasks = $stmt->get_result();
while($row = $result_tasks->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

// ==========================================
// 4. เช็คว่า User คนนี้ลงทะเบียนไปแล้วหรือยัง
// ==========================================
$is_registered = false;
$reg_status = '';
$sql_check = "SELECT registration_status FROM activity_registrations WHERE user_id = ? AND activity_id = ? AND registration_status != 'cancelled'";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $user_id, $activity_id);
$stmt->execute();
$result_check = $stmt->get_result();
if ($result_check->num_rows > 0) {
    $is_registered = true;
    $reg_data = $result_check->fetch_assoc();
    $reg_status = $reg_data['registration_status'];
}
$stmt->close();

// การตั้งค่ารูปภาพปกและสีสัน
$cover_img = !empty($activity['cover_image']) ? 'uploads/covers/' . $activity['cover_image'] : '';
$gradients = [
    'linear-gradient(45deg, #3a7bd5, #00d2ff)',
    'linear-gradient(45deg, #12c2e9, #c471ed)',
    'linear-gradient(45deg, #00b09b, #96c93d)',
    'linear-gradient(45deg, #f12711, #f5af19)'
];
$current_gradient = $gradients[$activity['activity_id'] % 4];
$header_bg = $cover_img ? "url('$cover_img') center/cover" : $current_gradient;

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดกิจกรรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root { --primary-color: #4e73df; --sidebar-width: 250px; }
    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fc; margin: 0; }
    .nav-item a { color: white; margin-right: 1rem; }
    .navbar { padding: 20px; }
    .nav-link:hover { color: white; }
    .main-content { margin: 30px; padding: 20px; }

    /* Custom styles for activity details */
    .activity-header {
        height: 300px;
        border-radius: 20px 20px 0 0;
        position: relative;
    }
    .activity-header-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4); border-radius: 20px 20px 0 0;
    }
    .activity-badge {
        position: absolute; top: 20px; right: 20px;
        padding: 8px 20px; border-radius: 30px; font-weight: bold; color: #fff;
    }
    .detail-card {
        border: none; border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px;
    }
    .icon-box {
        width: 45px; height: 45px; border-radius: 12px;
        background-color: #eff2f7; color: #4e73df;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .task-card {
        border: 2px solid #e3e6f0; border-radius: 15px; cursor: pointer; transition: 0.3s;
    }
    .task-card:hover { border-color: #4e73df; background-color: #f8f9fc; }
    
    .task-input:checked + label .task-card {
        border-color: #4e73df; background-color: #eef2ff;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    
    .btn-purple { background-color: #96a1cd; color: white; border: none; transition: 0.3s; }
    .btn-purple:hover { background-color: #7e89b3; color: white; }
    .bg-purple { background-color: #96a1cd !important; }

    @media (max-width: 768px) {
        .main-content { margin: 15px; padding: 10px; }
        .activity-header { height: 200px; }
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" style="cursor: pointer;"></i>
            <div class="nav-item"><a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a></div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">รายการ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-unstyled">
                <li><a href="report_activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li><a href="e-portfolio.php" class="text-white text-decoration-none d-block py-2"><i class="fa-regular fa-address-book"></i> E-Portfolio</a></li>
                <li><a href="transcript.php" class="text-white text-decoration-none d-block py-2"><i class="fa-regular fa-file-lines"></i> Transcript</a></li>
                <li><a href="score_activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="user_management.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid max-w-4xl mx-auto" style="max-width: 900px;">
            
            <a href="activity.php" class="btn mb-3 px-3">
                <i class="fa-solid fa-arrow-left me-2"></i>ย้อนกลับ
            </a>

            <div class="card detail-card">
                <div class="activity-header" style="background: <?php echo $header_bg; ?>;">
                    <div class="activity-header-overlay"></div>
                    <?php 
                        $status_class = 'bg-success';
                        $status_text = 'เปิดรับสมัคร (Open)';
                        if($activity['status'] == 'closed') { $status_class = 'bg-danger'; $status_text = 'ปิดรับสมัคร (Closed)'; }
                        if($activity['status'] == 'completed') { $status_class = 'bg-secondary'; $status_text = 'สิ้นสุดกิจกรรม (Completed)'; }
                    ?>
                    <span class="activity-badge <?php echo $status_class; ?> shadow-sm">
                        <?php echo $status_text; ?>
                    </span>
                </div>

                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-4"><?php echo htmlspecialchars($activity['title']); ?></h2>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fa-regular fa-calendar"></i></div>
                            <div>
                                <small class="text-muted d-block">วันที่จัดกิจกรรม</small>
                                <strong class="text-dark">
                                    <?php echo date('d/m/Y H:i', strtotime($activity['start_date'])); ?> - <br>
                                    <?php echo date('d/m/Y H:i', strtotime($activity['end_date'])); ?>
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <small class="text-muted d-block">สถานที่</small>
                                <strong class="text-dark"><?php echo htmlspecialchars($activity['location'] ?? 'ไม่ระบุ'); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="icon-box me-3"><i class="fa-regular fa-clock"></i></div>
                            <div>
                                <small class="text-muted d-block">จำนวนชั่วโมง</small>
                                <strong class="text-dark"><?php echo intval($activity['hours_count']); ?> ชั่วโมง (กยศ.)</strong>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3 border-bottom pb-2">รายละเอียดกิจกรรม</h5>
                    <p class="text-muted" style="line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                    </p>

                    <div class="mt-5 bg-light p-4 rounded-4 border">
                        <h5 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-clipboard-user me-2"></i>ลงทะเบียนเข้าร่วมกิจกรรม</h5>
                        
                        <?php if ($is_registered): ?>
                            <?php 
                                $reg_alert = 'alert-warning'; $reg_msg = 'รอการอนุมัติ';
                                if($reg_status == 'approved') { $reg_alert = 'alert-success'; $reg_msg = 'ได้รับการอนุมัติแล้ว'; }
                                if($reg_status == 'rejected') { $reg_alert = 'alert-danger'; $reg_msg = 'ถูกปฏิเสธการเข้าร่วม'; }
                            ?>
                            <div class="alert <?php echo $reg_alert; ?> text-center mb-0">
                                <i class="fa-solid fa-info-circle me-2"></i> คุณได้ลงทะเบียนกิจกรรมนี้ไปแล้ว (สถานะ: <strong><?php echo $reg_msg; ?></strong>)
                            </div>

                        <?php elseif ($activity['status'] != 'open'): ?>
                            <div class="alert alert-danger text-center mb-0">
                                <i class="fa-solid fa-circle-xmark me-2"></i> กิจกรรมนี้ <strong>ปิดรับสมัคร</strong> หรือ <strong>สิ้นสุดลงแล้ว</strong> ไม่สามารถลงทะเบียนได้
                            </div>
                        
                        <?php else: ?>
                            <form action="" method="POST" id="joinForm">
                                <p class="mb-3 text-muted">กรุณาเลือกฝ่าย/หน้าที่ ที่ต้องการเข้าร่วม:</p>
                                <div class="row g-3">
                                    <?php foreach($tasks as $task): 
                                        $is_exceeded = ($task['current_reg'] >= $task['capacity'] && $task['capacity'] > 0);
                                    ?>
                                    <div class="col-md-6">
                                        <input class="form-check-input d-none task-input" type="radio" name="task_id" id="task_<?php echo $task['task_id']; ?>" value="<?php echo $task['task_id']; ?>" required>
                                        <label class="w-100" for="task_<?php echo $task['task_id']; ?>">
                                            <div class="task-card p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($task['task_name']); ?></h6>
                                                    
                                                    <span class="badge <?php echo $is_exceeded ? 'bg-danger' : 'bg-primary'; ?> rounded-pill">
                                                        <?php echo $task['current_reg']; ?> / <?php echo $task['capacity']; ?> คน
                                                    </span>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($task['task_detail'] ?? 'ไม่มีรายละเอียดเพิ่มเติม'); ?></small>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="text-center mt-4 pt-3 border-top">
                                    <button type="submit" name="join_activity" class="btn btn-purple btn-lg px-5 rounded-pill shadow-sm fw-bold">
                                        <i class="fa-solid fa-paper-plane me-2"></i> ยืนยันการเข้าร่วมกิจกรรม
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['status_modal'])): ?>
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: none;">
                <div class="modal-header <?php echo ($_SESSION['status_modal']['type'] == 'success') ? 'bg-purple' : 'bg-danger'; ?> text-white border-0">
                    <h5 class="modal-title fw-bold"><?php echo $_SESSION['status_modal']['title']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas <?php echo ($_SESSION['status_modal']['type'] == 'success') ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mb-3" style="font-size: 4rem;"></i>
                    <h5 class="text-dark"><?php echo $_SESSION['status_modal']['message']; ?></h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-purple px-5 rounded-pill" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
        myModal.show();
    });
    </script>
    <?php unset($_SESSION['status_modal']); endif; ?>

</body>
</html>