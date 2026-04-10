<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userrole'])) {
    header("Location: index.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// ดึงข้อมูลผู้ใช้งานจริงสำหรับแสดงผลที่ Navbar
$stmt_user_profile = $conn->prepare("SELECT first_name, last_name, profile_image, year_level, academic_year, department FROM users WHERE user_id = ?");
$stmt_user_profile->bind_param("i", $user_id);
$stmt_user_profile->execute();
$user_info = $stmt_user_profile->get_result()->fetch_assoc();

$first_name = $user_info['first_name'] ?? 'ผู้ใช้งาน';
$full_name = ($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? '');
// ตรวจสอบรูปโปรไฟล์ ถ้าไม่มีให้ใช้รูป default
$profile_image = !empty($user_info['profile_image']) ? $user_info['profile_image'] : 'default.png';

// ดึงรายการกิจกรรม 6 รายการล่าสุด
$stmt_table = $conn->prepare("
    SELECT a.title, a.hours_count 
    FROM activity_registrations ar 
    JOIN activities a ON ar.activity_id = a.activity_id 
    WHERE ar.user_id = ? 
    ORDER BY ar.registered_at DESC LIMIT 6
");
$stmt_table->bind_param("i", $user_id);
$stmt_table->execute();
$result_table = $stmt_table->get_result();
$activities_table = [];
while ($row = $result_table->fetch_assoc()) {
    $activities_table[] = $row;
}

// ดึงข้อมูลสำหรับกราฟ
$stmt_chart = $conn->prepare("
    SELECT a.title, a.hours_count 
    FROM activity_registrations ar 
    JOIN activities a ON ar.activity_id = a.activity_id 
    WHERE ar.user_id = ? AND ar.participation_status = 'passed'
    ORDER BY ar.registered_at DESC LIMIT 5
");
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

$chart_labels = [];
$chart_data = [];
if ($result_chart && $result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_labels[] = mb_substr($row['title'], 0, 10) . '...';
        $chart_data[] = $row['hours_count'];
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&family=Prompt:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9; 
        --table-header: #D9D9D9;
        --table-row: #EFEFEF;
        --btn-blue: #6358E1;
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

    .login-pill-btn:hover {
        background: #eee;
        color: black;
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
        padding: 30px;
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .ui-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.05);
    }

    .chart-container {
        flex: 1.2;
        min-width: 320px;
    }

    .table-container {
        flex: 1;
        min-width: 320px;
    }

    .section-title {
        font-weight: 700;
        margin-bottom: 25px;
        font-size: 18px;
        color: #333;
        text-align: center;
    }

    
    .search-wrap {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
    }

    .search-input-group {
        background: #D9D9D9;
        border-radius: 10px;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        width: 200px;
    }

    .search-input-group input {
        border: none;
        background: transparent;
        font-size: 13px;
        outline: none;
        width: 100%;
        color: #333;
    }

    .table-responsive-custom {
        overflow-x: auto;
        width: 100%;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
        min-width: 300px;
    }

    .custom-table th {
        background: var(--table-header);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
        color: #333;
    }

    .custom-table td {
        background: var(--table-row);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
        color: #555;
    }

    .custom-table tr td:first-child {
        border-radius: 8px 0 0 8px;
    }

    .custom-table tr td:last-child {
        border-radius: 0 8px 8px 0;
    }

    .pagination-bar {
        background: #D9D9D9;
        padding: 12px 15px;
        border-radius: 8px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-purple {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 6px 25px;
        font-size: 13px;
        font-weight: 500;
        transition: 0.2s;
    }

    .btn-purple:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    
    @media (max-width: 991px) {
        .content-area {
            padding: 20px;
        }
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
            padding: 15px;
            flex-direction: column;
            gap: 20px;
        }

        .chart-container, .table-container {
            width: 100%;
            flex: none;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 10px !important;
        }
        
        .logout-area {
            margin-left: 10px;
        }
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
                    <span class="text-page-pill-btn mt-1">สถิติการเข้าร่วมกิจกรรม</span>
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
            </aside>

            <main class="content-area">
                <section class="chart-container ui-card">
                    <div class="section-title">สถิติการเข้าร่วมกิจกรรม</div>
                    <div style="height: 350px; width: 100%;">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                </section>

                <section class="table-container ui-card">
                    <div class="search-wrap">
                        <div class="search-input-group">
                            <i class="fa fa-search me-2" style="color: #666;"></i>
                            <input type="text" placeholder="ค้นหา">
                        </div>
                    </div>
                    <div class="table-responsive-custom">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="70%">ชื่อกิจกรรม</th>
                                    <th width="30%">ชั่วโมง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities_table)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">ยังไม่มีประวัติการเข้าร่วมกิจกรรม</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($activities_table as $act): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($act['title']); ?></td>
                                        <td><?php echo number_format($act['hours_count']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php for($i=count($activities_table); $i<6; $i++): ?>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
        // ระบบเลื่อน Sidebar ในมือถือ (เหมือนฝั่ง Admin)
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        // ปิด Sidebar เมื่อกดพื้นที่ว่าง
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
    });

    // ระบบสร้างกราฟ (ใช้ข้อมูลจริงจาก PHP)
    // ระบบสร้างกราฟ (ใช้ข้อมูลจริงจาก PHP)
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('userActivityChart').getContext('2d');
        const labels = <?php echo json_encode($chart_labels); ?>;
        const dataCounts = <?php echo json_encode($chart_data); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ชั่วโมงกิจกรรมที่ได้รับ',
                        data: dataCounts,
                        backgroundColor: '#00004d',
                        barPercentage: 0.6,
                        borderRadius: 4
                    }
                    // ลบชุดข้อมูล 'เป้าหมาย' ที่เป็น Mock data (dataCounts.map(v => v + 2)) ออกไปแล้ว
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true, // เปิดให้แสดง Legend เพื่อให้รู้ว่ากราฟแท่งคืออะไร
                        position: 'top'
                    } 
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        // ลบ max: 100 ออก เพื่อให้กราฟปรับสเกลแกน Y อัตโนมัติตามข้อมูลชั่วโมงที่มีจริง
                        ticks: { stepSize: 1 } 
                    },
                    x: { 
                        grid: { display: false } 
                    }
                }
            }
        });
    });
    </script>
</body>
</html>