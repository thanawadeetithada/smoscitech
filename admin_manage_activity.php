<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// ==========================================
// ส่วนรับคำสั่ง AJAX อัปเดตสถานะแบบ Real-time
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $reg_id = intval($_POST['reg_id']);
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'approved', 'rejected'])) {
        $upd_sql = "UPDATE activity_registrations SET registration_status = ? WHERE registration_id = ?";
        $stmt_upd = $conn->prepare($upd_sql);
        $stmt_upd->bind_param("si", $new_status, $reg_id);
        
        if ($stmt_upd->execute()) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก']);
        }
        $stmt_upd->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'สถานะไม่ถูกต้อง']);
    }
    exit(); // หยุดการทำงานไฟล์เพื่อส่งคืนแค่ JSON
}
// ==========================================

$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM activity_registrations WHERE activity_id = a.activity_id AND registration_status != 'cancelled') as reg_count,
        (SELECT SUM(capacity) FROM activity_tasks WHERE activity_id = a.activity_id) as total_cap
        FROM activities a WHERE a.activity_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();
$activity = $result->fetch_assoc();

if (!$activity) {
    die("ไม่พบข้อมูลกิจกรรม");
}

$sql_reg = "SELECT r.*, u.first_name, u.last_name, u.idstudent, t.task_name 
            FROM activity_registrations r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN activity_tasks t ON r.task_id = t.task_id
            WHERE r.activity_id = ? 
            ORDER BY r.registered_at DESC";
$stmt_reg = $conn->prepare($sql_reg);
$stmt_reg->bind_param("i", $activity_id);
$stmt_reg->execute();
$registrations = $stmt_reg->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกิจกรรม: <?php echo htmlspecialchars($activity['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <style>
    :root {
        --primary-color: #4e73df;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fc;
    }

    .nav-item a { color: white; margin-right: 1rem; }
    .navbar { padding: 20px; }
    .nav-link:hover { color: white; }
    
    .card { border: none; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
    .main-content { padding: 20px; margin: 20px auto; max-width: 1200px; }
    
    .stats-card {
        border-radius: 15px; border: none; transition: transform 0.3s;
        background: white; box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .table-container {
        background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .bg-purple { background-color: #96a1cd !important; color: white; }

    .status-select { font-weight: bold; border-radius: 8px; cursor: pointer; }
    .status-pending { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
    .status-approved { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .status-rejected { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

    @media (max-width: 768px) {
        .main-content { margin: 10px; padding: 10px; }
        .header-flex { flex-direction: column; align-items: flex-start !important; }
        .header-buttons { margin-top: 15px; width: 100%; display: flex; gap: 10px; }
        .header-buttons .btn { flex: 1; }
        .stats-card h2 { font-size: 1.4rem; }
    }

    .table th, .table td { white-space: nowrap; font-size: 0.9rem; vertical-align: middle; }
    .flex-container { display: flex; align-items: baseline; }
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
                <li><a href="admin_report_activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="admin_activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li><a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2"><i class="fa-regular fa-address-book"></i> E-Portfolio / Transcript</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 header-flex">
            <div>
                <div class="flex-container">
                    <a href="admin_activity.php" class="btn mb-2 me-3"><i class="fas fa-arrow-left"></i></a>
                    <h5 class="offcanvas-title text-muted">อนุมัติกิจกรรม</h5>
                </div>
                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($activity['title']); ?></h2>
                <span class="badge bg-<?php echo ($activity['status'] == 'open') ? 'success' : 'danger'; ?>">
                    สถานะกิจกรรม: <?php echo ucfirst($activity['status']); ?>
                </span>
            </div>
            <div class="header-buttons">
                <button class="btn btn-warning shadow-sm" onclick="prepareClose(<?php echo $activity_id; ?>)">
                     ปิดรับ
                </button>
                <button class="btn btn-danger shadow-sm" onclick="prepareDelete(<?php echo $activity_id; ?>)">
                    <i class="fas fa-trash"></i> ลบกิจกรรม
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">ผู้สมัครปัจจุบัน</h5>
                    <h5 class="fw-bold mb-0 text-primary" style="font-size: 1.2rem;"><?php echo $activity['reg_count']; ?> / <?php echo ($activity['total_cap'] ?? 0); ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">วันเริ่มงาน</h5>
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 1rem;"><?php echo date('d/m/Y', strtotime($activity['start_date'])); ?></h5>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">สถานที่</h5>
                    <h5 class="fw-bold text-truncate mb-0 text-dark" style="font-size: 1rem;"><?php echo htmlspecialchars($activity['location'] ?? 'ไม่ระบุ'); ?></h5>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>รายชื่อผู้ลงทะเบียน</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ฝ่าย/หน้าที่</th>
                            <th>วันที่สมัคร</th>
                            <th class="text-center">สถานะอนุมัติ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($registrations->num_rows > 0): ?>
                            <?php while($reg = $registrations->fetch_assoc()): 
                                $select_class = 'status-pending';
                                if($reg['registration_status'] == 'approved') $select_class = 'status-approved';
                                if($reg['registration_status'] == 'rejected') $select_class = 'status-rejected';
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($reg['idstudent']); ?></td>
                                <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($reg['task_name'] ?: 'ไม่ระบุ'); ?></span></td>
                                <td class="small text-muted"><?php echo date('d/m/y H:i', strtotime($reg['registered_at'])); ?></td>
                                <td class="text-center">
                                    <select class="form-select form-select-sm status-select <?php echo $select_class; ?>" 
                                            data-reg-id="<?php echo $reg['registration_id']; ?>" 
                                            style="min-width: 130px; margin: 0 auto;">
                                        <option value="pending" class="bg-white text-dark" <?php if($reg['registration_status'] == 'pending') echo 'selected'; ?>>รอพิจารณา</option>
                                        <option value="approved" class="bg-white text-dark" <?php if($reg['registration_status'] == 'approved') echo 'selected'; ?>>✓ อนุมัติแล้ว</option>
                                        <option value="rejected" class="bg-white text-dark" <?php if($reg['registration_status'] == 'rejected') echo 'selected'; ?>>✗ ปฏิเสธ</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fa-3x mb-3 opacity-50"></i><br>
                                    ยังไม่มีผู้ลงทะเบียนในกิจกรรมนี้
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 20px; border: none;">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title">ยืนยันการลบกิจกรรม</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-3">
                    <i class="fas fa-exclamation-triangle text-danger fa-4x mb-3"></i>
                    <p class="fs-5 mb-1">คุณต้องการลบกิจกรรมนี้หรือไม่?</p>
                    <small class="text-danger">*ข้อมูลผู้สมัครและคะแนนทั้งหมดที่เกี่ยวข้องจะถูกลบออกถาวร</small>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" class="btn btn-danger px-4 rounded-pill" id="confirmDeleteBtn">ลบข้อมูล</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmCloseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center" style="border-radius: 20px; border: none;">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold">ยืนยันการปิดรับสมัคร</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-3">
                    <i class="fas fa-lock text-warning fa-4x mb-3"></i>
                    <p class="fs-5 mb-1">คุณต้องการปิดรับสมัครกิจกรรมนี้หรือไม่?</p>
                    <small class="text-muted">เมื่อปิดแล้ว นักศึกษาจะไม่สามารถลงทะเบียนเพิ่มได้อีก</small>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" class="btn btn-warning px-4 rounded-pill fw-bold" id="confirmCloseBtn">ยืนยันปิดรับสมัคร</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // จัดการลบกิจกรรม
    function prepareDelete(id) {
        const myModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.href = 'process_delete_activity.php?id=' + id;
        myModal.show();
    }

    // จัดการปิดรับสมัครกิจกรรม
    function prepareClose(id) {
        const myModal = new bootstrap.Modal(document.getElementById('confirmCloseModal'));
        const confirmBtn = document.getElementById('confirmCloseBtn');
        // ส่ง ID ไปหน้า process_close_activity.php เพื่ออัปเดตสถานะในฐานข้อมูล
        confirmBtn.href = 'process_close_activity.php?id=' + id;
        myModal.show();
    }

    // จัดการเปลี่ยนสถานะผู้สมัครแบบ AJAX
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const selectElement = this;
            const regId = selectElement.dataset.regId;
            const newStatus = selectElement.value;
            
            // เปลี่ยนสีพื้นหลัง dropdown ตามสถานะที่เลือก
            selectElement.classList.remove('status-pending', 'status-approved', 'status-rejected');
            if(newStatus === 'pending') selectElement.classList.add('status-pending');
            else if(newStatus === 'approved') selectElement.classList.add('status-approved');
            else if(newStatus === 'rejected') selectElement.classList.add('status-rejected');

            // ส่งข้อมูลไปบันทึกผ่าน Fetch API (AJAX)
            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('reg_id', regId);
            formData.append('status', newStatus);

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // ใช้ SweetAlert2 แจ้งเตือนมุมขวาบนแบบไม่รบกวนการทำงาน
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: data.message, showConfirmButton: false, timer: 1500
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'ระบบขัดข้อง', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
            });
        });
    });
    </script>
</body>
</html>