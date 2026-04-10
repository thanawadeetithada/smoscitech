<?php
session_start();
include 'db.php';

$allowed_roles = ['club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// --- ดึงข้อมูลรูปโปรไฟล์และชื่อสำหรับ Top Navbar ---
$user_id_logged = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id_logged);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';
$stmt_profile->close();
// ---------------------------------------------------------

$current_activity_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($current_activity_id)) {
    header("Location: admin_score_activity.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scores'])) {
    if (isset($_POST['scores']) && is_array($_POST['scores'])) {
        $update_sql = "UPDATE activity_registrations SET participation_status = ? WHERE registration_id = ?";
        $stmt = $conn->prepare($update_sql);
        
        foreach ($_POST['scores'] as $reg_id => $status) {
            if (in_array($status, ['waiting', 'passed', 'not_passed'])) {
                $stmt->bind_param("si", $status, $reg_id);
                $stmt->execute();
            }
        }
        $stmt->close();
        $_SESSION['success_msg'] = "บันทึกผลการประเมินเรียบร้อยแล้ว";
    }
    header("Location: admin_detail_score_activity.php?id=" . $current_activity_id);
    exit();
}

$act_title_sql = "SELECT title FROM activities WHERE activity_id = ?";
$stmt = $conn->prepare($act_title_sql);
$stmt->bind_param("i", $current_activity_id);
$stmt->execute();
$act_result = $stmt->get_result();
$activity_data = $act_result->fetch_assoc();
$activity_title = $activity_data ? $activity_data['title'] : "ไม่พบชื่อกิจกรรม";

$participants = [];
$reg_ids = []; 
$sql_participants = "SELECT 
                        ar.registration_id, 
                        ar.participation_status,
                        u.idstudent AS student_id, 
                        u.first_name, 
                        u.last_name, 
                        t.task_name
                     FROM activity_registrations ar
                     JOIN users u ON ar.user_id = u.user_id
                     LEFT JOIN activity_tasks t ON ar.task_id = t.task_id
                     WHERE ar.activity_id = ? AND ar.registration_status = 'approved'
                     ORDER BY u.idstudent ASC";
$stmt = $conn->prepare($sql_participants);
$stmt->bind_param("i", $current_activity_id);
$stmt->execute();
$result_participants = $stmt->get_result();
while ($row = $result_participants->fetch_assoc()) {
    $participants[] = $row;
    $reg_ids[] = $row['registration_id'];
}
$stmt->close();

$evidences = [];
if (!empty($reg_ids)) {
    $placeholders = implode(',', array_fill(0, count($reg_ids), '?'));
    $sql_evidence = "SELECT * FROM activity_evidences WHERE registration_id IN ($placeholders)";
    $stmt_ev = $conn->prepare($sql_evidence);
    
    $types = str_repeat('i', count($reg_ids));
    $stmt_ev->bind_param($types, ...$reg_ids);
    $stmt_ev->execute();
    $result_ev = $stmt_ev->get_result();
    
    while ($ev = $result_ev->fetch_assoc()) {
        $evidences[$ev['registration_id']][] = $ev;
    }
    $stmt_ev->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ให้คะแนน: <?php echo htmlspecialchars($activity_title); ?> - SMO SCITECH</title>
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

    .max-w-1200 {
        max-width: 1200px;
        margin: 0 auto;
    }

    .ui-card {
        background: white;
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .card-header-custom {
        background-color: #fff;
        border-bottom: 2px solid #f0f0f0;
        padding: 1.5rem;
    }

    .btn-outline-custom {
        border: 1px solid #ccc;
        background: white;
        border-radius: 8px;
        color: #555;
        font-weight: 500;
        transition: 0.3s;
    }
    
    .btn-outline-custom:hover { background: #f0f0f0; color: #333; }

    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: 600;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 88, 225, 0.3);
    }

    
    .table th {
        background-color: #f8f9fa;
        color: var(--top-bar-bg);
        font-weight: 600;
        border-bottom: 2px solid #eee;
        padding: 15px;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        color: #444;
        border-bottom: 1px solid #f0f0f0;
    }

    .radio-custom {
        transform: scale(1.3);
        margin-right: 5px;
        cursor: pointer;
    }
    
    .radio-custom:checked {
        background-color: currentColor;
    }

    .evidence-img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
    }

    
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
        
        .table-responsive { border: 0; }
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
                    <span class="text-page-pill-btn mt-1">ให้คะแนนกิจกรรม</span>
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
                <a href="admin_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="admin_e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'academic_officer'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                     <i class="fa-solid fa-users"></i>
                   <span>ข้อมูลสมาชิกสโมสร / นายกสโมสร / รองนายกสโมสร </span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                     <i class="fa-solid fa-users"></i>
                   <span>ข้อมูลสมาชิกสโมสร</span>
                </a>
                <?php endif; ?>

                <a href="admin_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-cubes"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_score_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>ข้อมูลการเข้าร่วมกิจกรรม</span>
                </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], ['academic_officer', 'club_president'])): ?>
                <a href="admin_transcript.php" class="sidebar-item">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Transcript</span>
                </a>
                <?php endif; ?>
            </aside>

            <main class="content-area">
                <div class="max-w-1200">
                    
                    <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-left: 5px solid #198754;">
                        <i class="fa-solid fa-circle-check me-2"></i> <?php echo $_SESSION['success_msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_msg']); endif; ?>

                    <div class="d-flex align-items-center mb-4">
                        <a href="admin_score_activity.php" class="btn btn-outline-custom p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">ให้คะแนนผลการเข้าร่วมกิจกรรม</h4>
                            <p class="text-muted mb-0 small">กิจกรรม: <span class="fw-bold text-primary"><?php echo htmlspecialchars($activity_title); ?></span></p>
                        </div>
                    </div>

                    <div class="ui-card">
                        <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-check me-2" style="color: var(--top-bar-bg);"></i>รายชื่อผู้เข้าร่วมที่ได้รับการอนุมัติ</h5>
                            <span class="badge bg-primary rounded-pill px-3 py-2 fw-normal" style="font-size: 13px;">ทั้งหมด <?php echo count($participants); ?> คน</span>
                        </div>

                        <div class="card-body p-0">
                            <?php if (count($participants) > 0): ?>
                            <form method="POST" action="admin_detail_score_activity.php?id=<?php echo htmlspecialchars($current_activity_id); ?>">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-3 text-center" width="5%">ลำดับ</th>
                                                <th class="py-3" width="15%">รหัสนักศึกษา</th>
                                                <th class="py-3" width="20%">ชื่อ - นามสกุล</th>
                                                <th class="py-3" width="15%">หน้าที่/ฝ่าย</th>
                                                <th class="py-3 text-center" width="15%">หลักฐาน</th>
                                                <th class="py-3 text-center" width="30%">ผลการประเมิน</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                                $i = 1;
                                                foreach ($participants as $user): 
                                                    $reg_id = $user['registration_id'];
                                                    $current_status = $user['participation_status'];
                                                    $has_evidence = isset($evidences[$reg_id]) && count($evidences[$reg_id]) > 0;
                                                ?>
                                            <tr>
                                                <td class="px-4 text-center text-muted"><?php echo $i++; ?></td>
                                                <td><span class="badge bg-light text-dark border px-2 py-1"><?php echo htmlspecialchars($user['student_id']); ?></span></td>
                                                <td class="fw-bold text-dark">
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                </td>
                                                <td>
                                                    <?php echo !empty($user['task_name']) ? '<span class="text-secondary"><i class="fa-solid fa-tag me-1" style="font-size:10px;"></i>'.htmlspecialchars($user['task_name']).'</span>' : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($has_evidence): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#evidenceModal_<?php echo $reg_id; ?>">
                                                        <i class="fa-solid fa-image me-1"></i> ดูหลักฐาน
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary opacity-50 fw-normal">ยังไม่ส่งหลักฐาน</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center" style="background-color: #fafafa;">
                                                    <div class="d-flex justify-content-center gap-3">
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input radio-custom" style="color: #ffc107;"
                                                                type="radio" name="scores[<?php echo $reg_id; ?>]"
                                                                id="wait_<?php echo $reg_id; ?>" value="waiting"
                                                                <?php echo ($current_status == 'waiting' || empty($current_status)) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label text-warning fw-bold"
                                                                for="wait_<?php echo $reg_id; ?>">รอผล</label>
                                                        </div>
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input radio-custom" style="color: #198754;"
                                                                type="radio" name="scores[<?php echo $reg_id; ?>]"
                                                                id="pass_<?php echo $reg_id; ?>" value="passed"
                                                                <?php echo ($current_status == 'passed') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label text-success fw-bold"
                                                                for="pass_<?php echo $reg_id; ?>"><i
                                                                    class="fa-solid fa-check"></i> ผ่าน</label>
                                                        </div>
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input radio-custom" style="color: #dc3545;"
                                                                type="radio" name="scores[<?php echo $reg_id; ?>]"
                                                                id="fail_<?php echo $reg_id; ?>" value="not_passed"
                                                                <?php echo ($current_status == 'not_passed') ? 'checked' : ''; ?>>
                                                            <label class="form-check-label text-danger fw-bold"
                                                                for="fail_<?php echo $reg_id; ?>"><i
                                                                    class="fa-solid fa-xmark"></i> ไม่ผ่าน</label>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="p-4 bg-light border-top text-end">
                                    <button type="submit" name="save_scores" class="btn btn-purple-custom px-5 py-2">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกผลการประเมิน
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-regular fa-folder-open fa-4x text-muted mb-3 opacity-50"></i>
                                <h5 class="text-muted fw-normal">ยังไม่มีรายชื่อผู้เข้าร่วมที่ได้รับการอนุมัติในกิจกรรมนี้</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php 
    if (count($participants) > 0) {
        foreach ($participants as $user): 
            $reg_id = $user['registration_id'];
            if (isset($evidences[$reg_id]) && count($evidences[$reg_id]) > 0):
    ?>
    <div class="modal fade" id="evidenceModal_<?php echo $reg_id; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none; overflow: hidden;">
                <div class="modal-header text-white" style="background-color: var(--top-bar-bg);">
                    <h5 class="modal-title"><i class="fa-solid fa-file-invoice me-2"></i> หลักฐานการเข้าร่วมกิจกรรม</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light p-4">
                    
                    <div class="mb-4 text-center border-bottom pb-3">
                        <span class="badge bg-white text-dark border px-3 py-2 fs-6 shadow-sm">
                            <i class="fa-solid fa-user me-2 text-primary"></i> 
                            <?php echo htmlspecialchars($user['student_id']); ?> - <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </span>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($evidences[$reg_id] as $ev): 
                            $file = isset($ev['image_path']) ? $ev['image_path'] : ''; 
                            $text = isset($ev['description']) ? $ev['description'] : '';
                        ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 12px;">
                                <?php if (!empty($text)): ?>
                                <div class="mb-3 p-3 rounded" style="background-color: #f8f9fa; border-left: 4px solid var(--btn-blue);">
                                    <strong class="d-block mb-1 text-dark"><i class="fa-solid fa-comment-dots me-2 text-muted"></i>ข้อความอธิบาย:</strong>
                                    <span class="text-secondary"><?php echo nl2br(htmlspecialchars($text)); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($file)): ?>
                                    <?php 
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                                    ?>
                                    <div class="text-center">
                                        <img src="uploads/evidences/<?php echo htmlspecialchars($file); ?>" class="img-fluid rounded border shadow-sm" alt="หลักฐาน" style="max-height: 400px; object-fit: contain;">
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-3">
                                        <a href="uploads/evidences/<?php echo htmlspecialchars($file); ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4">
                                            <i class="fa-solid fa-download me-2"></i> ดาวน์โหลดไฟล์หลักฐาน (<?php echo strtoupper($ext); ?>)
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
    <?php 
            endif;
        endforeach; 
    } 
    ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
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