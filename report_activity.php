<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userrole'])) {
    header("Location: index.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$stmt_user = $conn->prepare("SELECT year_level, academic_year, department FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();

$u_year = $user_info['year_level'] ?? '';
$u_acad = $user_info['academic_year'] ?? '';
$u_dept = $user_info['department'] ?? '';

$stmt_joined = $conn->prepare("SELECT COUNT(*) as total FROM activity_registrations WHERE user_id = ? AND registration_status != 'rejected'");
$stmt_joined->bind_param("i", $user_id);
$stmt_joined->execute();
$total_joined = $stmt_joined->get_result()->fetch_assoc()['total'];

$stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM activity_registrations WHERE user_id = ? AND registration_status = 'pending'");
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$total_pending = $stmt_pending->get_result()->fetch_assoc()['total'];

$sql_upcoming = "
    SELECT title, start_date, hours_count 
    FROM activities 
    WHERE status = 'open' 
    AND start_date >= CURDATE() 
    AND (allowed_year_level IS NULL OR allowed_year_level = '' OR allowed_year_level LIKE ?)
    AND (allowed_academic_year IS NULL OR allowed_academic_year = '' OR allowed_academic_year LIKE ?)
    AND (allowed_department IS NULL OR allowed_department = '' OR allowed_department LIKE ?)
    ORDER BY start_date ASC 
    LIMIT 3
";
$stmt_upcoming = $conn->prepare($sql_upcoming);

$like_year = "%" . $u_year . "%";
$like_acad = "%" . $u_acad . "%";
$like_dept = "%" . $u_dept . "%";
$stmt_upcoming->bind_param("sss", $like_year, $like_acad, $like_dept);
$stmt_upcoming->execute();
$result_upcoming = $stmt_upcoming->get_result();

$stmt_chart = $conn->prepare("
    SELECT a.title, a.hours_count 
    FROM activity_registrations ar 
    JOIN activities a ON ar.activity_id = a.activity_id 
    WHERE ar.user_id = ? 
    ORDER BY ar.registered_at DESC LIMIT 5
");
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

$chart_labels = [];
$chart_data = [];
if ($result_chart && $result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_labels[] = $row['title'];
        $chart_data[] = $row['hours_count'];
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
        overflow-x: hidden;
    }

    .nav-item a {
        color: white;
        margin-right: 1rem;
    }

    .navbar {
        padding: 15px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-link:hover {
        color: #d1d3e2;
    }

    .main-content {
        margin: 30px;
        padding: 20px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        border: none;
        height: 100%;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .event-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f1f1f1;
    }

    .event-item:last-child {
        border-bottom: none;
    }

    .event-date {
        background: #eef2ff;
        color: var(--primary-color);
        padding: 8px 10px;
        border-radius: 10px;
        text-align: center;
        min-width: 70px;
        margin-right: 15px;
        line-height: 1.2;
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
        <div class="container-fluid p-0">
            <h4 class="fw-bold mb-4">ภาพรวมกิจกรรมและการดำเนินงานของสโมสร</h4>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <a href="activity.php" class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-bold mb-1">กิจกรรมที่เข้าร่วม</div>
                                <h2 class="mb-0 fw-bold text-primary"><?php echo number_format($total_joined); ?></h2>
                            </div>
                            <div class="stat-icon bg-primary text-white"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="text-decoration-none text-dark">
                        <div class="stat-card d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted fw-bold mb-1">รอการอนุมัติ</div>
                                <h2 class="mb-0 fw-bold text-warning"><?php echo number_format($total_pending); ?></h2>
                            </div>
                            <div class="stat-icon bg-warning text-white"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-4"><i class="fas fa-chart-line text-primary me-2"></i>
                            ชั่วโมงที่ได้รับจากกิจกรรมล่าสุด (5 รายการ)</h5>
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt text-warning me-2"></i> กิจกรรมเร็วๆ นี้
                        </h5>

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
                                <span
                                    style="font-size: 20px; font-weight: 700; display: block;"><?php echo $day; ?></span>
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
                            echo '<div class="text-center text-muted mt-4 p-4 bg-light rounded"><i class="far fa-folder-open fs-1 mb-2 text-secondary"></i><br>ยังไม่มีกิจกรรมที่คุณสามารถเข้าร่วมได้ในขณะนี้</div>';
                        }
                        ?>

                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="activity.php" class="text-decoration-none fw-bold text-primary">ดูกิจกรรมทั้งหมด <i
                                    class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('userActivityChart');
        if (ctx) {
            const labels = <?php echo json_encode($chart_labels); ?>;
            const dataCounts = <?php echo json_encode($chart_data); ?>;

            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'จำนวนชั่วโมงที่ได้รับ',
                        data: dataCounts,
                        backgroundColor: '#1cc88a',
                        hoverBackgroundColor: '#17a673',
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
                                font: {
                                    family: 'Prompt'
                                }
                            },
                            grid: {
                                borderDash: [5, 5]
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Prompt'
                                }
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
                            titleFont: {
                                family: 'Prompt',
                                size: 14
                            },
                            bodyFont: {
                                family: 'Prompt',
                                size: 13
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
</body>

</html>