<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

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
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>ให้คะแนน: <?php echo htmlspecialchars($activity_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #4e73df;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fc;
        margin: 0;
    }

    .nav-item a {
        color: white;
        margin-right: 1rem;
    }

    .navbar {
        padding: 20px;
    }

    .main-content {
        margin: 30px 50px;
        padding: 20px;
    }

    .card-header-custom {
        background-color: #fff;
        border-bottom: 2px solid #e3e6f0;
        padding: 1.5rem;
    }

    .table th {
        background-color: #f8f9fc;
        color: #4e73df;
    }

    .radio-custom {
        transform: scale(1.2);
        margin-right: 5px;
        cursor: pointer;
    }

    .evidence-img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
    }

    @media (max-width: 768px) {
        .main-content {
            margin: 15px;
            padding: 10px;
        }

        .table-responsive {
            border: 0;
        }
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
                <li><a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio / Transcript</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">

            <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo $_SESSION['success_msg']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_msg']); endif; ?>

            <div class="mb-4">
                <a href="admin_score_activity.php" class="btn btn-sm mb-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ
                </a>
                <h3 class="fw-bold mb-0 text-gray-800"><i class="fa-regular fa-star text-primary me-2"></i> ให้คะแนน:
                    <?php echo htmlspecialchars($activity_title); ?></h3>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">รายชื่อผู้เข้าร่วมที่ได้รับการอนุมัติ</h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2">ทั้งหมด <?php echo count($participants); ?>
                        คน</span>
                </div>

                <div class="card-body p-0">
                    <?php if (count($participants) > 0): ?>
                    <form method="POST"
                        action="admin_detail_score_activity.php?id=<?php echo htmlspecialchars($current_activity_id); ?>">

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3" width="5%">ลำดับ</th>
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
                                        <td class="px-4"><?php echo $i++; ?></td>
                                        <td><span
                                                class="badge bg-light text-dark border"><?php echo htmlspecialchars($user['student_id']); ?></span>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </td>
                                        <td><?php echo !empty($user['task_name']) ? htmlspecialchars($user['task_name']) : '<span class="text-muted">-</span>'; ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($has_evidence): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#evidenceModal_<?php echo $reg_id; ?>">
                                                <i class="fa-solid fa-paperclip"></i> ดูหลักฐาน
                                            </button>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">ยังไม่ส่งหลักฐาน</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center bg-light">
                                            <div class="d-flex justify-content-center gap-3">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input radio-custom border-warning"
                                                        type="radio" name="scores[<?php echo $reg_id; ?>]"
                                                        id="wait_<?php echo $reg_id; ?>" value="waiting"
                                                        <?php echo ($current_status == 'waiting' || empty($current_status)) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label text-warning fw-bold"
                                                        for="wait_<?php echo $reg_id; ?>">รอผล</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input radio-custom border-success"
                                                        type="radio" name="scores[<?php echo $reg_id; ?>]"
                                                        id="pass_<?php echo $reg_id; ?>" value="passed"
                                                        <?php echo ($current_status == 'passed') ? 'checked' : ''; ?>>
                                                    <label class="form-check-label text-success fw-bold"
                                                        for="pass_<?php echo $reg_id; ?>"><i
                                                            class="fa-solid fa-check"></i> ผ่าน</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input radio-custom border-danger"
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

                        <div class="p-4 bg-white border-top text-end">
                            <button type="submit" name="save_scores" class="btn btn-success px-5 py-2 fw-bold"
                                style="border-radius: 10px;">
                                <i class="fa-solid fa-floppy-disk me-2"></i> บันทึกผลการประเมิน
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-folder-open fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">ยังไม่มีรายชื่อผู้เข้าร่วมที่ได้รับการอนุมัติในกิจกรรมนี้</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

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
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-file-invoice me-2"></i> หลักฐานของ
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        <?php foreach ($evidences[$reg_id] as $ev): 
                            $file = isset($ev['image_path']) ? $ev['image_path'] : ''; 
                            $text = isset($ev['description']) ? $ev['description'] : '';
                        ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-3">
                                <?php if (!empty($text)): ?>
                                <p class="mb-2 border-bottom pb-2"><strong>ข้อความอธิบาย:</strong>
                                    <?php echo nl2br(htmlspecialchars($text)); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($file)): ?>
                                <?php 
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                                    ?>
                                <img src="uploads/evidences/<?php echo htmlspecialchars($file); ?>" class="evidence-img"
                                    alt="หลักฐาน">
                                <?php else: ?>
                                <a href="uploads/evidences/<?php echo htmlspecialchars($file); ?>" target="_blank"
                                    class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="fa-solid fa-download"></i> ดาวน์โหลดไฟล์ (<?php echo strtoupper($ext); ?>)
                                </a>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
    <?php 
            endif;
        endforeach; 
    } 
    ?>

</body>

</html>