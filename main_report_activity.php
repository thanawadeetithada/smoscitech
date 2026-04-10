<?php
session_start();
include 'db.php';

// หน้าสาธารณะ ไม่จำเป็นต้องดึงข้อมูลรูปโปรไฟล์ส่วนตัว
// ป้องกัน Error สำหรับบุคคลทั่วไปที่ไม่ได้ล็อกอิน
$profile_image = 'default.png';

// ดึงข้อมูลกิจกรรมและจำนวนผู้ลงทะเบียนจริงจาก Database
$sql_chart = "SELECT a.title, COUNT(ar.registration_id) as total_reg 
              FROM activities a 
              LEFT JOIN activity_registrations ar ON a.activity_id = ar.activity_id 
              GROUP BY a.activity_id 
              ORDER BY a.start_date DESC LIMIT 6"; 
$result_chart = $conn->query($sql_chart);

$chart_labels = [];
$chart_data = [];
$table_rows = [];

if ($result_chart && $result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_labels[] = mb_substr($row['title'], 0, 15) . '...';
        $chart_data[] = $row['total_reg'];
        $table_rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติการเข้าร่วมกิจกรรม - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&family=Prompt:wght@400;600&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #FFFFFF;
        --table-header: #D9D9D9;
        --table-row: #EFEFEF;
        --btn-blue: #6358E1;
    }

    body,
    html {
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

    .brand-logo {
        width: 60px;
        height: 60px;
    }

    .brand-name {
        font-size: clamp(16px, 4vw, 24px);
        font-family: serif;
        letter-spacing: 1px;
        white-space: nowrap;
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

    .login-pill-btn:hover {
        background: #eee;
        color: black;
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

    .sidebar-item span {
        font-weight: bold;
        font-size: 13px;
    }

    
    .content-area {
        flex-grow: 1;
        padding: 70px 40px;
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .chart-container {
        flex: 1.2;
        min-width: 320px;
        text-align: center;
    }

    .table-container {
        flex: 1;
        min-width: 400px;
    }

    .section-title {
        font-weight: bold;
        margin-bottom: 30px;
        font-size: 18px;
    }

    
    .search-wrap {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 10px;
    }

    .search-input-group {
        background: #D9D9D9;
        border-radius: 10px;
        padding: 5px 15px;
        display: flex;
        align-items: center;
        width: 170px;
    }

    .search-input-group input {
        border: none;
        background: transparent;
        font-size: 12px;
        outline: none;
        width: 100%;
    }

    
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .custom-table th {
        background: var(--table-header);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
    }

    .custom-table td {
        background: var(--table-row);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
    }

    .custom-table tr td:first-child {
        border-radius: 5px 0 0 5px;
    }

    .custom-table tr td:last-child {
        border-radius: 0 5px 5px 0;
    }

    .pagination-bar {
        background: #D9D9D9;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 10px;
    }

    .btn-purple {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 5px 25px;
        font-size: 14px;
    }

    
    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            top: 0;
            left: -230px;
            height: 100%;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar.active {
            left: 0;
        }

        .top-navbar {
            padding: 10px 15px;
        }

        .brand-name {
            font-size: 18px;
        }

        .content-area {
            padding: 20px 10px;
            flex-direction: column;
        }

        .chart-container,
        .table-container {
            min-width: 100%;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
        }
    }

    .username-pill-btn {
        background: white;
        color: black;
        padding: 6px 25px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        transition: 0.3s;
    }

    .username-pill-btn:hover {
        background: #eee;
        color: black;
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

    .logout-area {
        text-align: center;
        margin-left: 20px;
    }

    .logout-icon {
        width: 45px;
        display: block;
        margin: 0 auto;
        filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.2));
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
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn"
                    style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">สถิติการเข้าร่วมกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <a href="index.php" style="text-decoration: none;">
                    <i class="fa-solid fa-circle-user ms-3" style="font-size: 40px; color: #333;"></i>
                </a>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar">
                <a href="main_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="main_e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>
            </aside>

            <main class="content-area">
                <section class="chart-container">
                    <div class="section-title">สถิติการเข้าร่วมกิจกรรม</div>
                    <div style="height: 350px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </section>

                <section class="table-container">
                    <div class="search-wrap">
                        <div class="search-input-group">
                            <i class="fa fa-search me-2" style="font-size: 12px; color: #666;"></i>
                            <input type="text" placeholder="ค้นหา">
                        </div>
                    </div>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th width="65%">ชื่อกิจกรรม</th>
                                <th width="35%">จำนวนสมาชิกที่เข้าร่วม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($table_rows)): ?>
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted">ไม่พบข้อมูล</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($table_rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo number_format($row['total_reg']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php for ($i = count($table_rows); $i < 6; $i++): ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <?php endfor; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination-bar">
                        <button class="btn-purple">กลับ</button>
                        <button class="btn-purple">ถัดไป</button>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Toggle Sidebar สำหรับมือถือ
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        // ปิด Sidebar หากคลิกพื้นที่อื่นบนหน้าจอ
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn')
                    .length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('activityChart').getContext('2d');
        const labels = <?php echo json_encode($chart_labels); ?>;
        const dataCounts = <?php echo json_encode($chart_data); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'จำนวนจริง',
                        data: dataCounts,
                        backgroundColor: '#00004d', // น้ำเงินเข้มตามรูป
                        barPercentage: 0.6,
                    },
                    {
                        label: 'เป้าหมาย',
                        data: dataCounts.map(v => v + 5), // จำลองแท่งคู่ตามรูป
                        backgroundColor: '#FEE799', // เหลืองทองตามรูป
                        barPercentage: 0.6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 25
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>

</html>