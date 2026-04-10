<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_evidence'])) {
    $reg_id = intval($_POST['registration_id']);
    $description = trim($_POST['description']);
    $image_path = '';

    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === 0) {
        $file_name = $_FILES['evidence_file']['name'];
        $file_tmp = $_FILES['evidence_file']['tmp_name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        
        if (in_array($ext, $allowed_ext)) {
            if (!is_dir('uploads/evidences')) {
                mkdir('uploads/evidences', 0777, true);
            }
            
            $new_file_name = 'user_' . $user_id . '_ev_' . time() . '.' . $ext;
            $upload_path = 'uploads/evidences/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_path = $new_file_name;
            }
        } else {
            $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ผิดพลาด', 'message' => 'รองรับเฉพาะไฟล์รูปภาพ หรือเอกสาร PDF/Word เท่านั้น'];
        }
    }

    if (!empty($description) || !empty($image_path)) {
        $sql_insert = "INSERT INTO activity_evidences (registration_id, image_path, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("iss", $reg_id, $image_path, $description);
        
        if ($stmt->execute()) {
            $_SESSION['status_modal'] = ['type' => 'success', 'title' => 'สำเร็จ', 'message' => 'ส่งหลักฐานเรียบร้อยแล้ว สโมสรจะทำการพิจารณาในภายหลัง'];
        } else {
            $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ผิดพลาด', 'message' => 'ไม่สามารถบันทึกข้อมูลได้'];
        }
        $stmt->close();
    }
    
    header("Location: score_activity.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_evidence'])) {
    $evidence_id = intval($_POST['evidence_id']);
    $sql_get = "SELECT ae.image_path 
                FROM activity_evidences ae 
                JOIN activity_registrations ar ON ae.registration_id = ar.registration_id 
                WHERE ae.evidence_id = ? 
                AND ar.user_id = ? 
                AND (ar.participation_status = 'waiting' OR ar.participation_status IS NULL OR ar.participation_status = '')";
                
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("ii", $evidence_id, $user_id);
    $stmt_get->execute();
    $res_get = $stmt_get->get_result();

    if ($row = $res_get->fetch_assoc()) {
        $file_to_delete = 'uploads/evidences/' . $row['image_path'];
        if (!empty($row['image_path']) && file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        $sql_del = "DELETE FROM activity_evidences WHERE evidence_id = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("i", $evidence_id);
        
        if ($stmt_del->execute()) {
            $_SESSION['status_modal'] = ['type' => 'success', 'title' => 'สำเร็จ', 'message' => 'ลบหลักฐานเรียบร้อยแล้ว'];
        } else {
            $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ผิดพลาด', 'message' => 'ไม่สามารถลบหลักฐานได้'];
        }
        $stmt_del->close();
    } else {
        $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ไม่อนุญาต', 'message' => 'ไม่สามารถลบได้ สโมสรได้ประเมินผลกิจกรรมนี้ไปแล้ว'];
    }
    $stmt_get->close();

    header("Location: score_activity.php");
    exit();
}

$approved_activities = [];
$reg_ids = [];

$sql_act = "SELECT 
                ar.registration_id, 
                ar.participation_status,
                a.activity_id,
                a.title,
                a.start_date,
                a.end_date,
                a.hours_count,
                a.cover_image,
                t.task_name
            FROM activity_registrations ar
            JOIN activities a ON ar.activity_id = a.activity_id
            LEFT JOIN activity_tasks t ON ar.task_id = t.task_id
            WHERE ar.user_id = ? AND ar.registration_status = 'approved'
            ORDER BY a.start_date DESC";
            
$stmt = $conn->prepare($sql_act);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_act = $stmt->get_result();

while ($row = $result_act->fetch_assoc()) {
    $approved_activities[] = $row;
    $reg_ids[] = $row['registration_id'];
}
$stmt->close();

$evidences = [];
if (!empty($reg_ids)) {
    $placeholders = implode(',', array_fill(0, count($reg_ids), '?'));
    $sql_ev = "SELECT * FROM activity_evidences WHERE registration_id IN ($placeholders)";
    $stmt_ev = $conn->prepare($sql_ev);
    
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
    <title>คะแนนกิจกรรม - SMO SCITECH</title>
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

    .max-w-1000 {
        max-width: 1000px;
        margin: 0 auto;
    }

    .act-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
        background: white;
    }

    .act-header {
        background: linear-gradient(45deg, var(--top-bar-bg), #C7A68C);
        padding: 20px 25px;
        color: white;
        position: relative;
    }

    .status-badge {
        font-size: 0.9rem;
        padding: 6px 15px;
        border-radius: 30px;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .evidence-box {
        background-color: #FAFAFA;
        border: 1px dashed #d1d3e2;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
        position: relative;
        transition: 0.3s;
    }

    .evidence-box:hover {
        border-color: var(--btn-blue);
        background-color: #F8F9FC;
    }

    .btn-delete-evidence {
        position: absolute;
        top: -10px;
        right: -10px;
        background: white;
        border: 1px solid #ffccd5;
        color: #dc3545;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-delete-evidence:hover {
        background-color: #dc3545;
        color: white;
        transform: scale(1.1);
    }

    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: 500;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
        transform: translateY(-2px);
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
        
        .act-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }
        .act-header h5 { max-width: 100% !important; }
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
                    <span class="text-page-pill-btn mt-1">คะแนนกิจกรรม</span>
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
                <a href="score_activity.php" class="sidebar-item mb-3">
                    <i class="fa-regular fa-star text-primary"></i>
                    <span class="text-primary">ส่งผลประเมิน</span>
                </a>
            </aside>

            <main class="content-area">
                <div class="max-w-1000">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">
                                <i class="fa-solid fa-medal text-warning me-2"></i>ผลการเข้าร่วมกิจกรรม
                            </h4>
                            <p class="text-muted mb-0 small">ส่งหลักฐานและติดตามผลการประเมินกิจกรรมของคุณ</p>
                        </div>
                    </div>

                    <?php if (count($approved_activities) > 0): ?>
                        <?php foreach ($approved_activities as $act): 
                            $reg_id = $act['registration_id'];
                            $status = $act['participation_status'];
                            $has_evidence = isset($evidences[$reg_id]);
                            
                            $badge_class = 'bg-warning text-dark';
                            $badge_text = '<i class="fa-solid fa-hourglass-half me-1"></i> รอผลประเมิน';
                            
                            if ($status == 'passed') {
                                $badge_class = 'bg-success text-white';
                                $badge_text = '<i class="fa-solid fa-check-circle me-1"></i> ผ่านกิจกรรม';
                            } elseif ($status == 'not_passed') {
                                $badge_class = 'bg-danger text-white';
                                $badge_text = '<i class="fa-solid fa-times-circle me-1"></i> ไม่ผ่านกิจกรรม';
                            }

                            // กำหนดรูปปกหรือ Gradient
                            $cover_img = !empty($act['cover_image']) ? 'uploads/covers/' . $act['cover_image'] : '';
                            $gradients = [
                                'linear-gradient(45deg, rgba(163, 126, 94, 0.9), rgba(199, 166, 140, 0.9))',
                                'linear-gradient(45deg, rgba(142, 112, 87, 0.9), rgba(180, 150, 124, 0.9))',
                                'linear-gradient(45deg, rgba(99, 88, 225, 0.9), rgba(139, 131, 230, 0.9))',
                                'linear-gradient(45deg, rgba(254, 239, 179, 0.9), rgba(242, 213, 117, 0.9))'
                            ];
                            $current_gradient = $gradients[$act['activity_id'] % 4];
                            $header_bg = $cover_img ? "linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('$cover_img') center/cover" : $current_gradient;
                        ?>

                        <div class="act-card">
                            <div class="act-header d-flex justify-content-between align-items-center" style="background: <?php echo $header_bg; ?>;">
                                <h5 class="mb-0 fw-bold text-truncate" style="max-width: 70%; text-shadow: 1px 1px 3px rgba(0,0,0,0.3);" title="<?php echo htmlspecialchars($act['title']); ?>">
                                    <?php echo htmlspecialchars($act['title']); ?>
                                </h5>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo $badge_text; ?>
                                </span>
                            </div>

                            <div class="card-body p-4">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong class="text-secondary"><i class="fa-solid fa-user-tag me-2"></i>หน้าที่/ฝ่าย:</strong> 
                                            <span class="text-dark"><?php echo htmlspecialchars($act['task_name'] ?? '-'); ?></span>
                                        </p>
                                        <p class="mb-2"><strong class="text-secondary"><i class="fa-regular fa-clock me-2"></i>ชั่วโมง:</strong> 
                                            <span class="text-dark"><?php echo $act['hours_count']; ?> ชั่วโมง (กยศ.)</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong class="text-secondary"><i class="fa-regular fa-calendar me-2"></i>วันที่จัด:</strong> 
                                            <span class="text-dark"><?php echo date('d/m/Y', strtotime($act['start_date'])); ?></span>
                                        </p>
                                    </div>
                                </div>

                                <hr style="opacity: 0.1;">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-paperclip me-2 text-primary"></i>หลักฐานที่ส่งแล้ว</h6>

                                    <?php if (empty($status) || $status == 'waiting'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-custom px-3 py-1" data-bs-toggle="modal" data-bs-target="#uploadModal_<?php echo $reg_id; ?>">
                                        <i class="fa-solid fa-plus me-1"></i> ส่งหลักฐานเพิ่ม
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($has_evidence): ?>
                                <div class="row g-3 mt-2">
                                    <?php foreach ($evidences[$reg_id] as $ev): ?>
                                    <div class="col-md-6">
                                        <div class="evidence-box">
                                            <?php if (empty($status) || $status == 'waiting'): ?>
                                            <button type="button" class="btn-delete-evidence" data-bs-toggle="modal" data-bs-target="#deleteEvidenceModal_<?php echo $ev['evidence_id']; ?>" title="ลบหลักฐาน">
                                                <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                                            </button>
                                            <?php endif; ?>

                                            <div class="d-flex gap-3 align-items-center">
                                                <?php if (!empty($ev['image_path'])): ?>
                                                    <?php 
                                                        $ext = strtolower(pathinfo($ev['image_path'], PATHINFO_EXTENSION));
                                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                                                    ?>
                                                    <a href="uploads/evidences/<?php echo $ev['image_path']; ?>" target="_blank" class="flex-shrink-0">
                                                        <img src="uploads/evidences/<?php echo $ev['image_path']; ?>" style="height: 65px; width: 65px; object-fit: cover; border-radius: 8px;" class="border shadow-sm">
                                                    </a>
                                                    <?php else: ?>
                                                    <a href="uploads/evidences/<?php echo $ev['image_path']; ?>" target="_blank" class="btn btn-light border text-primary flex-shrink-0" style="height: 65px; width: 65px; display:flex; align-items:center; justify-content:center; border-radius:8px;">
                                                        <i class="fa-solid fa-file-arrow-down fa-2x"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div class="flex-grow-1 pe-3">
                                                    <?php if (!empty($ev['description'])): ?>
                                                    <p class="small mb-0 text-muted" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                                        "<?php echo htmlspecialchars($ev['description']); ?>"
                                                    </p>
                                                    <?php else: ?>
                                                    <span class="small text-muted fst-italic">ไม่มีคำอธิบาย</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (empty($status) || $status == 'waiting'): ?>
                                    <div class="modal fade" id="deleteEvidenceModal_<?php echo $ev['evidence_id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-center p-3" style="border-radius: 20px; border: none;">
                                                <div class="modal-header border-0 pb-0 justify-content-end">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body pt-0 pb-4">
                                                    <div class="mb-3">
                                                        <div class="mx-auto d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                                            <i class="fas fa-trash text-danger fa-2x"></i>
                                                        </div>
                                                    </div>
                                                    <h5 class="fw-bold mb-2 text-dark">ต้องการลบหลักฐานนี้หรือไม่?</h5>
                                                    <p class="text-muted small mb-4">หากลบแล้วข้อมูลและไฟล์จะหายไปอย่างถาวร</p>
                                                    
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">ยกเลิก</button>
                                                        <form action="" method="POST" class="d-inline">
                                                            <input type="hidden" name="evidence_id" value="<?php echo $ev['evidence_id']; ?>">
                                                            <button type="submit" name="delete_evidence" class="btn btn-danger px-4 rounded-pill fw-medium">ใช่, ลบหลักฐาน</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4 bg-light rounded-3 mt-2 border border-dashed">
                                    <i class="fa-solid fa-inbox text-muted mb-2 fs-3 opacity-50"></i>
                                    <p class="text-muted small mb-0">ยังไม่มีหลักฐานการเข้าร่วม</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal fade" id="uploadModal_<?php echo $reg_id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius: 15px; overflow: hidden; border: none;">
                                    <div class="modal-header text-white" style="background-color: var(--top-bar-bg);">
                                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i> ส่งหลักฐานการเข้าร่วม</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <div class="modal-body p-4 bg-light">
                                            <input type="hidden" name="registration_id" value="<?php echo $reg_id; ?>">

                                            <div class="mb-4">
                                                <label class="form-label fw-bold text-dark small">แนบไฟล์รูปภาพ หรือ เอกสาร</label>
                                                <input type="file" name="evidence_file" class="form-control" accept="image/*,.pdf,.doc,.docx" style="border-radius: 8px;">
                                                <div class="form-text mt-2 small"><i class="fa-solid fa-circle-info text-warning me-1"></i> รองรับไฟล์ .jpg, .png, .pdf, .docx</div>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label fw-bold text-dark small">อธิบาย/รายละเอียด (ถ้ามี)</label>
                                                <textarea name="description" class="form-control" rows="3" placeholder="พิมพ์ข้อความอธิบายหลักฐาน..." style="border-radius: 8px; resize: none;"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-white border-top-0 pt-0 pb-3 pe-4">
                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" name="upload_evidence" class="btn btn-purple-custom rounded-pill px-4 shadow-sm">บันทึกและส่ง</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center mt-4">
                            <i class="fa-regular fa-folder-open fa-4x text-muted mb-3 opacity-50"></i>
                            <h5 class="text-dark fw-bold">คุณยังไม่มีกิจกรรมที่ได้รับการอนุมัติให้เข้าร่วม</h5>
                            <p class="text-muted small">หากคุณลงทะเบียนกิจกรรมไปแล้ว กรุณารอแอดมินพิจารณาอนุมัติ</p>
                            <a href="activity.php" class="btn btn-purple-custom rounded-pill px-4 mt-3 py-2">ไปหน้าค้นหากิจกรรม</a>
                        </div>
                    <?php endif; ?>

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
                    <button type="button" class="btn btn-purple-custom px-5 rounded-pill" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['status_modal']); endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show Status Modal if present
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

        // Click outside to close sidebar
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