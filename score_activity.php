<?php
session_start();
include 'db.php';

// เช็คการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 1. จัดการการอัปโหลดหลักฐาน
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_evidence'])) {
    $reg_id = intval($_POST['registration_id']);
    $description = trim($_POST['description']);
    $image_path = '';

    // จัดการไฟล์อัปโหลด
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
            $_SESSION['status_modal'] = ['type' => 'success', 'title' => 'สำเร็จ', 'message' => 'ส่งหลักฐานเรียบร้อยแล้ว แอดมินจะทำการพิจารณาในภายหลัง'];
        } else {
            $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ผิดพลาด', 'message' => 'ไม่สามารถบันทึกข้อมูลได้'];
        }
        $stmt->close();
    }
    
    header("Location: score_activity.php");
    exit();
}

// ==========================================
// 1.5 จัดการการลบหลักฐาน (ล็อคให้ลบได้เฉพาะสถานะ waiting เท่านั้น)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_evidence'])) {
    $evidence_id = intval($_POST['evidence_id']);

    // ดึงข้อมูลเช็คความปลอดภัย: ต้องเป็นไฟล์ของตัวเอง และ สถานะต้องยังไม่ถูกประเมิน (waiting หรือ ว่างเปล่า)
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
        
        // ลบไฟล์ออกจากโฟลเดอร์
        if (!empty($row['image_path']) && file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        // ลบข้อมูลออกจากฐานข้อมูล
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
        // กรณีดักจับได้ว่าพยายามลบตอนสถานะเปลี่ยนไปแล้ว
        $_SESSION['status_modal'] = ['type' => 'error', 'title' => 'ไม่อนุญาต', 'message' => 'ไม่สามารถลบได้ แอดมินได้ประเมินผลกิจกรรมนี้ไปแล้ว'];
    }
    $stmt_get->close();

    header("Location: score_activity.php");
    exit();
}

// ==========================================
// 2. ดึงข้อมูลกิจกรรมที่ได้รับการอนุมัติแล้ว
// ==========================================
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

// ==========================================
// 3. ดึงหลักฐานที่เคยส่งไปแล้ว
// ==========================================
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
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้าคะแนนกิจกรรม</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #4e73df;
        --sidebar-width: 250px;
    }

    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fc; margin: 0; }
    .nav-item a { color: white; margin-right: 1rem; }
    .navbar { padding: 20px; }
    .nav-link:hover { color: white; }
    .main-content { margin: 30px; padding: 20px; max-width: 1000px; margin-left: auto; margin-right: auto; }
    
    .act-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
    .act-header { background: linear-gradient(45deg, #3a7bd5, #00d2ff); padding: 15px 20px; color: white; }
    .status-badge { font-size: 0.9rem; padding: 8px 15px; border-radius: 30px; font-weight: bold; }
    
    /* CSS สำหรับกล่องหลักฐานให้ลบได้ */
    .evidence-box { 
        background-color: #f8f9fc; 
        border: 1px dashed #d1d3e2; 
        border-radius: 10px; 
        padding: 15px; 
        margin-top: 15px; 
        position: relative; 
    }
    
    /* CSS ปุ่มถังขยะ */
    .btn-delete-evidence {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255, 255, 255, 0.8);
        border: 1px solid #ffccd5;
        color: #e74a3b;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn-delete-evidence:hover {
        background-color: #e74a3b;
        color: white;
        transform: scale(1.1);
    }

    .btn-purple { background-color: #96a1cd; color: white; border: none; }
    .btn-purple:hover { background-color: #7e89b3; color: white; }
    .bg-purple { background-color: #96a1cd !important; }

    @media (max-width: 768px) {
        .main-content { margin: 10px; padding: 10px; }
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link text-white" href="logout.php">
                    [ <?php echo !empty($_SESSION['userrole']) ? $_SESSION['userrole'] : 'User'; ?> ]
                    <i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout
                </a>
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
                <li><a href="report_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li><a href="e-portfolio.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio </a></li>
                <li><a href="transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-file-lines"></i> Transcript</a></li>
                <li><a href="score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-gray-800"><i class="fa-solid fa-medal text-warning me-2"></i> ผลการเข้าร่วมกิจกรรม</h4>
                <p class="text-muted mb-0">ส่งหลักฐานและติดตามผลการประเมินกิจกรรมของคุณ</p>
            </div>
        </div>

        <?php if (count($approved_activities) > 0): ?>
            <?php foreach ($approved_activities as $act): 
                $reg_id = $act['registration_id'];
                $status = $act['participation_status'];
                $has_evidence = isset($evidences[$reg_id]);

                // กำหนดป้ายสถานะ
                $badge_class = 'bg-warning text-dark';
                $badge_text = '<i class="fa-solid fa-hourglass-half"></i> รอผลประเมิน';
                
                if ($status == 'passed') {
                    $badge_class = 'bg-success text-white';
                    $badge_text = '<i class="fa-solid fa-check-circle"></i> ผ่านกิจกรรม';
                } elseif ($status == 'not_passed') {
                    $badge_class = 'bg-danger text-white';
                    $badge_text = '<i class="fa-solid fa-times-circle"></i> ไม่ผ่านกิจกรรม';
                }
            ?>
            
            <div class="act-card card">
                <div class="act-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-truncate" style="max-width: 70%;" title="<?php echo htmlspecialchars($act['title']); ?>">
                        <?php echo htmlspecialchars($act['title']); ?>
                    </h5>
                    <span class="status-badge shadow-sm <?php echo $badge_class; ?>">
                        <?php echo $badge_text; ?>
                    </span>
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong class="text-primary"><i class="fa-solid fa-user-tag me-1"></i> หน้าที่/ฝ่าย:</strong> <?php echo htmlspecialchars($act['task_name'] ?? '-'); ?></p>
                            <p class="mb-1"><strong class="text-primary"><i class="fa-regular fa-clock me-1"></i> ชั่วโมง:</strong> <?php echo $act['hours_count']; ?> ชั่วโมง</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong class="text-primary"><i class="fa-regular fa-calendar me-1"></i> วันที่จัด:</strong> <?php echo date('d/m/Y', strtotime($act['start_date'])); ?></p>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-paperclip me-1 text-secondary"></i> หลักฐานที่ส่งแล้ว</h6>
                        
                        <?php if (empty($status) || $status == 'waiting'): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadModal_<?php echo $reg_id; ?>">
                                <i class="fa-solid fa-plus"></i> ส่งหลักฐานเพิ่ม
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_evidence): ?>
                        <div class="row g-2 mt-2">
                            <?php foreach ($evidences[$reg_id] as $ev): ?>
                                <div class="col-md-6">
                                    <div class="evidence-box">
                                        <?php if (empty($status) || $status == 'waiting'): ?>
                                            <button type="button" class="btn-delete-evidence" data-bs-toggle="modal" data-bs-target="#deleteEvidenceModal_<?php echo $ev['evidence_id']; ?>" title="ลบหลักฐาน">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (!empty($ev['description'])): ?>
                                            <p class="small mb-2 text-muted pe-4">"<?php echo htmlspecialchars($ev['description']); ?>"</p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($ev['image_path'])): ?>
                                            <?php 
                                            $ext = strtolower(pathinfo($ev['image_path'], PATHINFO_EXTENSION));
                                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): 
                                            ?>
                                                <a href="uploads/evidences/<?php echo $ev['image_path']; ?>" target="_blank">
                                                    <img src="uploads/evidences/<?php echo $ev['image_path']; ?>" style="height: 60px; width: 60px; object-fit: cover; border-radius: 8px;" class="border">
                                                </a>
                                                <span class="small ms-2 text-primary">คลิกเพื่อดูรูป</span>
                                            <?php else: ?>
                                                <a href="uploads/evidences/<?php echo $ev['image_path']; ?>" target="_blank" class="btn btn-sm btn-light border text-primary">
                                                    <i class="fa-solid fa-file-arrow-down"></i> ไฟล์แนบ
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (empty($status) || $status == 'waiting'): ?>
                                <div class="modal fade" id="deleteEvidenceModal_<?php echo $ev['evidence_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content shadow-lg" style="border-radius: 20px; border: none;">
                                            <div class="modal-header bg-danger text-white border-0">
                                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> ยืนยันการลบหลักฐาน</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center py-4">
                                                <div class="mb-4 mt-2">
                                                    <div style="width: 80px; height: 80px; background-color: #ffe5e5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                                        <i class="fa-regular fa-trash-can text-danger" style="font-size: 2.5rem;"></i>
                                                    </div>
                                                </div>
                                                <h5 class="text-dark fw-bold mb-2">ต้องการลบหลักฐานนี้หรือไม่?</h5>
                                                <p class="text-muted small mb-0">หากลบแล้วข้อมูลและไฟล์จะหายไปอย่างถาวร</p>
                                            </div>
                                            <div class="modal-footer border-0 justify-content-center pb-4">
                                                <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="evidence_id" value="<?php echo $ev['evidence_id']; ?>">
                                                    <button type="submit" name="delete_evidence" class="btn btn-danger px-4 rounded-pill shadow-sm">ยืนยันการลบ</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 bg-light rounded-3 mt-2 border">
                            <p class="text-muted small mb-0">ยังไม่มีหลักฐานการเข้าร่วม</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal fade" id="uploadModal_<?php echo $reg_id; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i> ส่งหลักฐานการเข้าร่วม</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="modal-body p-4">
                                <input type="hidden" name="registration_id" value="<?php echo $reg_id; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-primary">อธิบาย/รายละเอียด (ถ้ามี)</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="พิมพ์ข้อความอธิบายหลักฐาน..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-primary">แนบไฟล์รูปภาพ หรือ เอกสาร</label>
                                    <input type="file" name="evidence_file" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                    <div class="form-text mt-2"><i class="fa-solid fa-circle-info text-warning"></i> รองรับไฟล์รูปภาพ, PDF, Word</div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light border-0">
                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" name="upload_evidence" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">บันทึกและส่ง</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center mt-4">
                <i class="fa-regular fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted fw-bold">คุณยังไม่มีกิจกรรมที่ได้รับการอนุมัติให้เข้าร่วม</h5>
                <p class="text-muted small">หากคุณลงทะเบียนกิจกรรมไปแล้ว กรุณารอแอดมินพิจารณาอนุมัติ</p>
                <a href="activity.php" class="btn btn-primary rounded-pill px-4 mt-3">ไปหน้าค้นหากิจกรรม</a>
            </div>
        <?php endif; ?>
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