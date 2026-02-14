<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าสร้างกิจกรรม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
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
        padding: 10px;
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

    .btn-purple {
        background-color: #96a1cd;
        color: white;
        border: none;
    }

    .btn-purple:hover {
        background-color: #7e89b3;
        color: white;
    }

    .bg-purple {
        background-color: #96a1cd !important;
    }

    .modal-content {
        border-radius: 15px;
        border: none;
        overflow: hidden;
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
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex align-items-center mb-4">
                    <a href="admin_activity.php" class="btn btn-light me-3"><i class="fas fa-arrow-left"></i></a>
                    <h3 class="fw-bold mb-0">สร้างกิจกรรมใหม่</h3>
                </div>

                <form action="process_create_activity.php" method="POST" enctype="multipart/form-data">
                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4"><i class="fas fa-info-circle me-2 text-primary"></i>ข้อมูลทั่วไป</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">รูปหน้าปกกิจกรรม (ถ้ามี)</label>
                                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                                    <small class="text-muted">รองรับไฟล์ .jpg, .jpeg, .png</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ชื่อกิจกรรม</label>
                                    <input type="text" name="title" class="form-control"
                                        placeholder="เช่น ค่ายอาสาพัฒนาชนบท" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">รายละเอียดกิจกรรม</label>
                                    <textarea name="description" class="form-control" rows="4"
                                        placeholder="ระบุวัตถุประสงค์หรือรายละเอียดสำคัญ..."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">สถานที่จัดกิจกรรม</label>
                                    <input type="text" name="location" class="form-control"
                                        placeholder="เช่น หอประชุมใหญ่">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">จำนวนชั่วโมงกิจกรรม (กยศ.)</label>
                                    <input type="number" name="hours_count" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">วัน/เวลาที่เริ่ม</label>
                                    <input type="datetime-local" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">วัน/เวลาที่สิ้นสุด</label>
                                    <input type="datetime-local" name="end_date" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>หน้าที่และขอบเขตงาน (Tasks)
                                </h5>
                                <button type="button" class="btn btn-sm btn-success" onclick="addTask()"><i
                                        class="fas fa-plus"></i> เพิ่มฝ่าย/หน้าที่</button>
                            </div>

                            <div id="tasks-container">
                                <div class="task-row">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="small fw-bold">ชื่อฝ่าย/หน้าที่</label>
                                            <input type="text" name="task_name[]" class="form-control form-control-sm"
                                                placeholder="เช่น ฝ่ายสถานที่" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small fw-bold">จำนวนที่รับสมัคร (คน)</label>
                                            <input type="number" name="task_capacity[]"
                                                class="form-control form-control-sm" value="10" min="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold">รายละเอียด (ระบุได้)</label>
                                            <input type="text" name="task_detail[]" class="form-control form-control-sm"
                                                placeholder="จัดเตรียมโต๊ะเก้าอี้">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="reset" class="btn btn-outline-secondary px-4">ล้างข้อมูล</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">บันทึกและสร้างกิจกรรม</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div
                    class="modal-header <?php echo (isset($_SESSION['status_modal']) && $_SESSION['status_modal']['type'] == 'success') ? 'bg-purple' : 'bg-danger'; ?> text-white border-0">
                    <h5 class="modal-title fw-bold" id="statusModalLabel">
                        <?php echo $_SESSION['status_modal']['title'] ?? ''; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <?php if(isset($_SESSION['status_modal']) && $_SESSION['status_modal']['type'] == 'success'): ?>
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <?php else: ?>
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                        <?php endif; ?>
                    </div>
                    <h5 class="text-dark"><?php echo $_SESSION['status_modal']['message'] ?? ''; ?></h5>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-purple px-4" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function addTask() {
        const container = document.getElementById('tasks-container');
        const div = document.createElement('div');
        div.className = 'task-row animate__animated animate__fadeIn';
        div.innerHTML = `
        <button type="button" class="btn-remove-task" onclick="this.parentElement.remove()">&times;</button>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="small fw-bold">ชื่อฝ่าย/หน้าที่</label>
                <input type="text" name="task_name[]" class="form-control form-control-sm" placeholder="เช่น ฝ่ายสวัสดิการ" required>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold">จำนวนที่รับสมัคร (คน)</label>
                <input type="number" name="task_capacity[]" class="form-control form-control-sm" value="5" min="1">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold">รายละเอียด (ระบุได้)</label>
                <input type="text" name="task_detail[]" class="form-control form-control-sm">
            </div>
        </div>
    `;
        container.appendChild(div);
    }
    </script>

    <?php if (isset($_SESSION['status_modal'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
        myModal.show();
    });
    </script>
    <?php 
    unset($_SESSION['status_modal']); 
endif; 
?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>