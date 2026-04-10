<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($activity_id === 0) {
    header("Location: activity.php");
    exit();
}

// --- ดึงข้อมูลรูปโปรไฟล์และชื่อสำหรับ Top Navbar ---
$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';
$stmt_profile->close();
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_activity'])) {
    $task_id = $_POST['task_id'];

    $insert_sql = "INSERT INTO activity_registrations (user_id, activity_id, task_id, registration_status, participation_status) 
                   VALUES (?, ?, ?, 'pending', 'waiting')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iii", $user_id, $activity_id, $task_id);
    
    if ($stmt->execute()) {
        $_SESSION['status_modal'] = [
            'type' => 'success',
            'title' => 'สำเร็จ',
            'message' => 'ลงทะเบียนเข้าร่วมกิจกรรมเรียบร้อยแล้ว รอการอนุมัติจากสโมสร'
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

$cover_img = !empty($activity['cover_image']) ? 'uploads/covers/' . $activity['cover_image'] : '';
$gradients = [
    'linear-gradient(45deg, #A37E5E, #C7A68C)',
    'linear-gradient(45deg, #8E7057, #B4967C)',
    'linear-gradient(45deg, #6358E1, #8B83E6)',
    'linear-gradient(45deg, #FEEFB3, #F2D575)'
];
$current_gradient = $gradients[$activity['activity_id'] % 4];
$header_bg = $cover_img ? "url('$cover_img') center/cover" : $current_gradient;

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดกิจกรรม - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9;
        --btn-blue: #6358E1;
        --text-dark: #333333;
    }

    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Sarabun', sans-serif;
        background-color: var(--light-bg);
        overflow-x: hidden;
    }

    .wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    
    .top-navbar {
        background-color: var(--top-bar-bg);
        min-height: 80px;
        display: flex;
        align-items: center;
        padding: 10px 20px;
        justify-content: space-between;
        color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 100;
        position: sticky;
        top: 0;
    }

    .brand-section {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-logo { width: 60px; height: 60px; }

    .brand-name {
        font-size: clamp(16px, 4vw, 24px);
        font-family: serif;
        letter-spacing: 1px;
        white-space: nowrap;
    }

    .text-page-pill-btn {
        background: white;
        color: black;
        padding: 3px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 13px;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .login-pill-btn {
        background: white;
        color: black;
        padding: 6px 25px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        transition: 0.3s;
    }

    .login-pill-btn:hover { background: #eee; color: black; }

    .logout-area {
        text-align: center;
        margin-left: 20px;
    }

    .logout-text {
        color: #000;
        font-weight: bold;
        text-decoration: none;
        font-size: 14px;
        background: #D9D9D9;
        padding: 2px 10px;
        border-radius: 5px;
        display: block;
    }

    
    .main-wrapper {
        display: flex;
        flex: 1;
        position: relative;
    }

    
    .sidebar {
        width: 230px;
        background-color: var(--yellow-sidebar);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
        transition: 0.3s ease-in-out;
        z-index: 99;
    }

    .sidebar-item {
        background: white;
        padding: 25px 10px;
        text-align: center;
        border-bottom: 1px solid #eee;
        text-decoration: none;
        color: #333;
        display: block;
        transition: all 0.3s ease;
    }

    .sidebar-item:hover {
        background: #FDFDFD;
        transform: translateX(5px);
    }

    .sidebar-item i {
        font-size: 32px;
        display: block;
        margin-bottom: 8px;
        color: #000;
    }

    .sidebar-item span { font-weight: bold; font-size: 13px; }

    
    .content-area {
        flex-grow: 1;
        padding: 30px;
    }

    .max-w-900 {
        max-width: 900px;
        margin: 0 auto;
    }

    .btn-outline-custom {
        border-radius: 8px;
        color: #555;
        font-weight: 500;
        transition: 0.3s;
    }
    
    .btn-outline-custom:hover { background: #f0f0f0; color: #333; }

    .detail-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .activity-header {
        height: 250px;
        position: relative;
    }

    .activity-header-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.3);
    }

    .activity-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: bold;
        color: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .icon-box {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background-color: rgba(163, 126, 94, 0.1);
        color: var(--top-bar-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .task-card {
        border: 2px solid #e3e6f0;
        border-radius: 15px;
        cursor: pointer;
        transition: 0.3s;
        background: white;
    }

    .task-card:hover {
        border-color: var(--btn-blue);
        background-color: #f8f9fc;
    }

    .task-input:checked + label .task-card {
        border-color: var(--btn-blue);
        background-color: rgba(99, 88, 225, 0.05);
        box-shadow: 0 0 0 0.25rem rgba(99, 88, 225, 0.1);
    }

    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 50px;
        transition: 0.3s;
        font-weight: 600;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 88, 225, 0.3);
    }

    
    .bg-purple { background-color: var(--btn-blue) !important; }

    
    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            top: 0;
            left: -230px;
            height: 100%;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        .sidebar.active { left: 0; }
        .top-navbar { padding: 10px 15px; }
        .brand-name { font-size: 18px; }
        .content-area { padding: 15px; }
        .logout-text { padding: 2px !important; font-size: 10px !important; }
        .logout-area { margin-left: 10px; }
        .activity-header { height: 180px; }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn" style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">รายละเอียดกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($first_name); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">Log out</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar">
                <a href="report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>E -portfolio</span>
                </a>
                <a href="user_management.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิก</span>
                </a>
                <a href="activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>
            </aside>

            <main class="content-area">
                <div class="max-w-900">
                    
                    <a href="activity.php" class="btn btn-outline-custom mb-3 px-3">
                        <i class="fa-solid fa-arrow-left me-2"></i>ย้อนกลับ
                    </a>

                    <div class="card detail-card bg-white">
                        <div class="activity-header" style="background: <?php echo $header_bg; ?>;">
                            <div class="activity-header-overlay"></div>
                            <?php 
                                $status_class = 'bg-success';
                                $status_text = 'เปิดรับสมัคร (Open)';
                                if($activity['status'] == 'closed') { $status_class = 'bg-danger'; $status_text = 'ปิดรับสมัคร (Closed)'; }
                                if($activity['status'] == 'completed') { $status_class = 'bg-secondary'; $status_text = 'สิ้นสุดกิจกรรม (Completed)'; }
                            ?>
                            <span class="activity-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <h2 class="fw-bold mb-4 text-dark"><?php echo htmlspecialchars($activity['title']); ?></h2>

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

                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark">รายละเอียดกิจกรรม</h5>
                            <p class="text-muted" style="line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                            </p>

                            <div class="mt-5 p-4 rounded-4 border" style="background: #FAFAFA;">
                                <h5 class="fw-bold mb-4" style="color: var(--top-bar-bg);">
                                    <i class="fa-solid fa-clipboard-user me-2"></i>ลงทะเบียนเข้าร่วมกิจกรรม
                                </h5>

                                <?php if ($is_registered): ?>
                                    <?php 
                                        $reg_alert = 'alert-warning'; $reg_msg = 'รอการอนุมัติ';
                                        if($reg_status == 'approved') { $reg_alert = 'alert-success'; $reg_msg = 'ได้รับการอนุมัติแล้ว'; }
                                        if($reg_status == 'rejected') { $reg_alert = 'alert-danger'; $reg_msg = 'ถูกปฏิเสธการเข้าร่วม'; }
                                    ?>
                                    <div class="alert <?php echo $reg_alert; ?> text-center mb-0 border-0 shadow-sm">
                                        <i class="fa-solid fa-info-circle me-2"></i> คุณได้ลงทะเบียนกิจกรรมนี้ไปแล้ว <br>
                                        สถานะปัจจุบัน: <strong><?php echo $reg_msg; ?></strong>
                                    </div>

                                <?php elseif ($activity['status'] != 'open'): ?>
                                    <div class="alert alert-danger text-center mb-0 border-0 shadow-sm">
                                        <i class="fa-solid fa-circle-xmark me-2"></i> กิจกรรมนี้ <strong>ปิดรับสมัคร</strong> หรือ
                                        <strong>สิ้นสุดลงแล้ว</strong>
                                    </div>

                                <?php else: ?>
                                    <form action="" method="POST" id="joinForm">
                                        <p class="mb-3 text-muted fw-medium">กรุณาเลือกหน้าที่ ที่ต้องการลงสมัคร:</p>
                                        <div class="row g-3">
                                            <?php foreach($tasks as $task): 
                                                    $is_exceeded = ($task['current_reg'] >= $task['capacity'] && $task['capacity'] > 0);
                                                ?>
                                            <div class="col-md-6">
                                                <input class="form-check-input d-none task-input" type="radio" name="task_id"
                                                    id="task_<?php echo $task['task_id']; ?>"
                                                    value="<?php echo $task['task_id']; ?>" required>
                                                <label class="w-100 h-100" for="task_<?php echo $task['task_id']; ?>">
                                                    <div class="task-card p-3 h-100 d-flex flex-column">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold mb-0 text-dark">
                                                                <?php echo htmlspecialchars($task['task_name']); ?>
                                                            </h6>
                                                            <span class="badge <?php echo $is_exceeded ? 'bg-danger' : 'bg-primary'; ?> rounded-pill" style="font-size: 11px;">
                                                                <?php echo $task['current_reg']; ?> / <?php echo $task['capacity']; ?> คน
                                                            </span>
                                                        </div>
                                                        <small class="text-muted mt-auto">
                                                            <?php echo htmlspecialchars($task['task_detail'] ?? 'ไม่มีรายละเอียดเพิ่มเติม'); ?>
                                                        </small>
                                                    </div>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="text-center mt-4 pt-3">
                                            <button type="submit" name="join_activity" class="btn btn-purple-custom btn-lg px-5 shadow-sm w-100 w-md-auto">
                                                <i class="fa-solid fa-paper-plane me-2"></i> ยืนยันการเข้าร่วมกิจกรรม
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
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
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-purple-custom px-5" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['status_modal']); endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show Modal if Session Exists
        var statusModalEl = document.getElementById('statusModal');
        if (statusModalEl) {
            var myModal = new bootstrap.Modal(statusModalEl);
            myModal.show();
        }

        // Mobile Sidebar Toggle
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
    });
    </script>
</body>
</html>