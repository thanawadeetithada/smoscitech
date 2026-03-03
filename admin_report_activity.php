<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$sql_activities = "SELECT COUNT(*) as total FROM activities";
$result_activities = $conn->query($sql_activities);
$total_activities = $result_activities ? $result_activities->fetch_assoc()['total'] : 0;

$sql_users = "SELECT COUNT(*) as total FROM users";
$result_users = $conn->query($sql_users);
$total_users = $result_users ? $result_users->fetch_assoc()['total'] : 0;

$sql_pending = "SELECT COUNT(*) as total FROM activity_registrations WHERE registration_status = 'pending'";
$result_pending = $conn->query($sql_pending);
$total_pending = $result_pending ? $result_pending->fetch_assoc()['total'] : 0;

$sql_upcoming = "SELECT title, start_date, hours_count 
                 FROM activities 
                 WHERE status = 'open' AND start_date >= CURDATE() 
                 ORDER BY start_date ASC 
                 LIMIT 3";
$result_upcoming = $conn->query($sql_upcoming);

$sql_chart = "SELECT a.title, COUNT(ar.registration_id) as total_reg 
              FROM activities a 
              LEFT JOIN activity_registrations ar ON a.activity_id = ar.activity_id 
              GROUP BY a.activity_id 
              ORDER BY a.start_date DESC LIMIT 5";
$result_chart = $conn->query($sql_chart);

$chart_labels = [];
$chart_data = [];
if ($result_chart && $result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_labels[] = $row['title'];
        $chart_data[] = $row['total_reg'];
    }
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
        margin: 30px;
        padding: 20px;
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
        <div class="container-fluid p-0">
            <h4 class="fw-bold mb-4 text-dark">ภาพรวมกิจกรรมและการดำเนินงานของสโมสร</h4>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <a href="admin_activity.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-bold mb-1">กิจกรรมทั้งหมด</div>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($total_activities); ?></h2>
                            </div>
                            <div class="stat-icon bg-primary text-white"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="admin_user_management.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-bold mb-1">สมาชิกทั้งหมด</div>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($total_users); ?></h2>
                            </div>
                            <div class="stat-icon bg-success text-white"><i class="fas fa-user-friends"></i></div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="admin_activity.php" class="text-decoration-none text-dark">
                    <div class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-bold mb-1">รอการอนุมัติ</div>
                                <h2 class="mb-0 fw-bold"><?php echo number_format($total_pending); ?></h2>
                            </div>
                            <div class="stat-icon bg-warning text-white"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-4"><i class="fas fa-chart-bar text-primary me-2"></i> สถิติการเข้าร่วมกิจกรรม (5 ล่าสุด)</h5>
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt text-warning me-2"></i> กิจกรรมเร็วๆ นี้</h5>

                        <?php 
                        $thai_months = [
                            "01" => "ม.ค.", "02" => "ก.พ.", "03" => "มี.ค.", "04" => "เม.ย.",
                            "05" => "พ.ค.", "06" => "มิ.ย.", "07" => "ก.ค.", "08" => "ส.ค.",
                            "09" => "ก.ย.", "10" => "ต.ค.", "11" => "พ.ย.", "12" => "ธ.ค."
                        ];

                        if ($result_upcoming && $result_upcoming->num_rows > 0) {
                            while ($row = $result_upcoming->fetch_assoc()) {
                                $date_obj = strtotime($row['start_date']);
                                $day = date('d', $date_obj);
                                $month_num = date('m', $date_obj);
                                $month_thai = $thai_months[$month_num];
                        ?>
                                <div class="event-item">
                                    <div class="event-date">
                                        <span style="font-size: 20px; font-weight: 700; display: block;"><?php echo $day; ?></span>
                                        <small style="font-size: 13px; font-weight: 500;"><?php echo $month_thai; ?></small>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['title']); ?></div>
                                        <div class="text-muted small">
                                            <i class="far fa-clock me-1"></i> <?php echo $row['hours_count']; ?> ชั่วโมง
                                        </div>
                                    </div>
                                </div>
                        <?php 
                            }
                        } else {
                            echo '<div class="text-center text-muted mt-4 p-4 bg-light rounded"><i class="far fa-folder-open fs-1 mb-2 text-secondary"></i><br>ยังไม่มีกิจกรรมเร็วๆ นี้</div>';
                        }
                        ?>

                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="admin_activity.php" class="text-decoration-none fw-bold text-primary">ดูกิจกรรมทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('activityChart').getContext('2d');

            const labels = <?php echo json_encode($chart_labels); ?>;
            const dataCounts = <?php echo json_encode($chart_data); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'จำนวนผู้ลงทะเบียน (คน)',
                        data: dataCounts,
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderRadius: 6,
                        barPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: { family: 'Prompt' }
                            },
                            grid: {
                                borderDash: [5, 5]
                            }
                        },
                        x: {
                            ticks: {
                                font: { family: 'Prompt' }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            titleFont: { family: 'Prompt', size: 14 },
                            bodyFont: { family: 'Prompt', size: 13 }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>