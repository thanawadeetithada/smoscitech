<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

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

$sql_reg = "SELECT r.*, u.first_name, u.last_name, u.username, t.task_name 
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
    <style>
    :root {
        --primary-color: #4e73df;
    }

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

    .main-content {
        padding: 20px;
        margin: 20px auto;
        max-width: 1200px;
    }

    .stats-card {
        border-radius: 15px;
        border: none;
        transition: transform 0.3s;
        background: white;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
    }

    .table-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .bg-purple {
        background-color: #96a1cd !important;
        color: white;
    }

    @media (max-width: 768px) {
        .main-content {
            margin: 10px;
            padding: 10px;
        }

        .header-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .header-buttons {
            margin-top: 15px;
            width: 100%;
            display: flex;
            gap: 10px;
        }

        .header-buttons .btn {
            flex: 1;
        }

        .stats-card h2 {
            font-size: 1.4rem;
        }
    }

    .table th,
    .table td {
        white-space: nowrap;
        font-size: 0.9rem;
    }

    .modal-confirm .modal-content {
        border-radius: 20px;
        border: none;
    }

    .modal-confirm .modal-header {
        border-bottom: none;
        padding: 25px 25px 5px;
    }

    .modal-confirm .modal-footer {
        border-top: none;
        padding: 10px 25px 25px;
    }

    .icon-box-danger {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8d7da;
        color: #dc3545;
        font-size: 40px;
        margin-bottom: 20px;
    }

    .flex-container {
        display: flex;
        align-items: baseline;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
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
                <li><a href="admin_e-portfolio.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio</a></li>
                <li><a href="admin_transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-file-lines"></i> Transcript</a></li>
                <li><a href="admin_approve_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-calendar-check"></i> อนุมัติกิจกรรม</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 header-flex">
            <div>
                <div class="flex-container">
                    <a href="admin_activity.php" class="btn mb-2 me-3"><i class="fas fa-arrow-left"></i>
                    </a>
                    <h5 class="offcanvas-title">อนุมัติกิจกกรม</h5>
                </div>
                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($activity['title']); ?></h2>
                <span class="badge bg-<?php echo ($activity['status'] == 'open') ? 'success' : 'danger'; ?>">
                    สถานะ: <?php echo ucfirst($activity['status']); ?>
                </span>
            </div>
            <div class="header-buttons">
                <button class="btn btn-danger shadow-sm" onclick="prepareDelete(<?php echo $activity_id; ?>)">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">ผู้สมัครปัจจุบัน</h5>
                    <h5 class="fw-bold mb-0" style="font-size: 1rem;"><?php echo $activity['reg_count']; ?> /
                        <?php echo ($activity['total_cap'] ?? 0); ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">วันเริ่มงาน</h5>
                    <h5 class="fw-bold mb-0" style="font-size: 1rem;">
                        <?php echo date('d/m/Y', strtotime($activity['start_date'])); ?></h5>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stats-card p-3 text-center">
                    <h5 class="text-muted small">สถานที่</h5>
                    <h5 class="fw-bold text-truncate mb-0" style="font-size: 1rem;">
                        <?php echo htmlspecialchars($activity['location']); ?></h5>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>รายชื่อผู้ลงทะเบียน</h5>
                <button class="btn btn-outline-success btn-sm px-3"><i class="fas fa-file-excel me-1"></i>
                    Export</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User/รหัส</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ฝ่าย/หน้าที่</th>
                            <th>สถานะสมัคร</th>
                            <th>วันที่สมัคร</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($registrations->num_rows > 0): ?>
                        <?php while($reg = $registrations->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($reg['username']); ?></td>
                            <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                            <td><span
                                    class="badge bg-info text-dark"><?php echo htmlspecialchars($reg['task_name'] ?: 'ไม่ระบุ'); ?></span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select"
                                    data-reg-id="<?php echo $reg['registration_id']; ?>" style="min-width: 120px;">
                                    <option value="pending"
                                        <?php if($reg['registration_status'] == 'pending') echo 'selected'; ?>>
                                        รอดำเนินการ</option>
                                    <option value="approved"
                                        <?php if($reg['registration_status'] == 'approved') echo 'selected'; ?>>
                                        อนุมัติแล้ว</option>
                                    <option value="rejected"
                                        <?php if($reg['registration_status'] == 'rejected') echo 'selected'; ?>>
                                        ปฏิเสธ</option>
                                </select>
                            </td>
                            <td class="small text-muted">
                                <?php echo date('d/m/y H:i', strtotime($reg['registered_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-light border"><i
                                        class="fas fa-eye text-primary"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">ยังไม่มีผู้ลงทะเบียน</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-3">
                    <p>คุณต้องการลบรายชื่อนี้หรือไม่?</p>
                    <small class="text-danger">*ข้อมูลที่เกี่ยวข้องทั้งหมดจะถูกลบออก</small>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <a href="#" class="btn btn-danger" id="confirmDeleteBtn">ลบ</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function prepareDelete(id) {
        const myModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        confirmBtn.href = 'process_delete_activity.php?id=' + id;

        myModal.show();
    }

    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const regId = this.dataset.regId;
            const newStatus = this.value;
            alert('กำลังเปลี่ยนสถานะรายการที่ ' + regId + ' เป็น: ' + newStatus);
        });
    });
    </script>
</body>

</html>