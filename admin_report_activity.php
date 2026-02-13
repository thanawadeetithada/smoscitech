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
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้าสถิติการเข้าร่วมกิจกรรม</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
    :root {
        --primary-color: #4e73df;
        --sidebar-width: 250px;
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

    .nav-link:hover {
        color: white;
    }

    .main-content {
        margin: 30px 50px;
        padding: 20px;
    }

    .topbar {
        background: white;
        padding: 15px 30px;
        margin-bottom: 30px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 .15rem 1.75rem 0 rgba(58, 59, 69, .05);
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        border: none;
        height: 100%;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
    }

    .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .event-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .event-date {
        background: #eef2ff;
        color: var(--primary-color);
        padding: 5px 10px;
        border-radius: 8px;
        text-align: center;
        font-weight: bold;
        min-width: 60px;
        margin-right: 15px;
    }

    .event-date small {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
    }

    .text-muted {
        font-size: 1.1rem;
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

    <div class="main-content">
        <div class="container-fluid">
            <h3 class="mb-1">ภาพรวมกิจกรรมและการดำเนินงานของสโมสร</h3><br>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <a href="admin_activity.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted">กิจกรรมทั้งหมด</div>
                                <h3 class="mb-0 fw-bold">24</h3>
                            </div>
                            <div class="stat-icon bg-primary text-white"><i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="admin_user_management.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">สมาชิกทั้งหมด</div>
                                <h3 class="mb-0 fw-bold">1,250</h3>
                            </div>
                            <div class="stat-icon bg-success text-white"><i class="fas fa-user-friends"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="admin_approve_activity.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">รอการอนุมัติ</div>
                                <h3 class="mb-0 fw-bold">5</h3>
                            </div>
                            <div class="stat-icon bg-warning text-white"><i class="fas fa-clock"></i></div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="stat-card">
                        <h6 class="fw-bold mb-4"><i class="fas fa-chart-line text-primary me-2"></i>
                            สถิติการเข้าร่วมกิจกรรม (ล่าสุด)</h6>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="stat-card">
                        <h6 class="fw-bold mb-3">กิจกรรมเร็วๆ นี้</h6>

                        <div class="event-item">
                            <div class="event-date">OCT<small>15</small></div>
                            <div>
                                <div class="fw-bold small">ค่ายอาสาพัฒนาชนบท</div>
                                <div class="text-muted" style="font-size: 12px;">บำเพ็ญประโยชน์</div>
                            </div>
                        </div>

                        <div class="event-item">
                            <div class="event-date">NOV<small>1</small></div>
                            <div>
                                <div class="fw-bold small">อบรม Python for Data Science</div>
                                <div class="text-muted" style="font-size: 12px;">วิชาการ</div>
                            </div>
                        </div>

                        <div class="event-item">
                            <div class="event-date">DEC<small>20</small></div>
                            <div>
                                <div class="fw-bold small">Sport Day 2023</div>
                                <div class="text-muted" style="font-size: 12px;">นันทนาการ</div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <a href="admin_activity.php" class="text-decoration-none small">ดูทั้งหมด</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

    </script>

</body>

</html>