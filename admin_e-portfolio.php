<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
// ถ้าไม่มีรูปให้ใช้ default.png
$profile_image = (!empty($user_data['profile_image']) && $user_data['profile_image'] != 'default.png') ? $user_data['profile_image'] : 'default.png';
$stmt_profile->close();

// ดึงข้อมูลจริงจากฐานข้อมูลสำหรับตาราง E-portfolio
$stmt = $conn->prepare("SELECT user_id, idstudent, email, first_name, last_name, userrole, department, academic_year, year_level 
                        FROM users WHERE deleted_at IS NULL ORDER BY first_name ASC");
$stmt->execute();
$result = $stmt->get_result();

$year_query = $conn->query("SELECT DISTINCT academic_year FROM users WHERE deleted_at IS NULL AND academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC");
$academic_years = [];
while($y = $year_query->fetch_assoc()) {
    $academic_years[] = $y['academic_year'];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน E-portfolio - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #FFFFFF;
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

    .brand-section { display: flex; align-items: center; gap: 12px; }
    .brand-logo { width: 60px; height: 60px; }
    .brand-name { font-size: clamp(16px, 4vw, 24px); font-family: serif; letter-spacing: 1px; white-space: nowrap; }

    .login-pill-btn {
        background: white; color: black; padding: 6px 25px; border-radius: 50px;
        text-decoration: none; font-weight: bold; font-size: 16px; transition: 0.3s;
    }
    .login-pill-btn:hover { background: #eee; color: black; }

    .text-page-pill-btn {
        background: white; color: black; padding: 3px 15px; border-radius: 5px;
        text-decoration: none; font-size: 13px; letter-spacing: 0.5px; font-weight: 500;
    }

    .logout-area { text-align: center; margin-left: 20px; }
    .logout-text {
        color: #000; font-weight: bold; text-decoration: none; font-size: 14px;
        background: #D9D9D9; padding: 2px 10px; border-radius: 5px; display: block;
    }

    
    .main-wrapper { display: flex; flex: 1; position: relative; }

    
    .sidebar {
        width: 230px; background-color: var(--yellow-sidebar); flex-shrink: 0;
        display: flex; flex-direction: column; border-right: 1px solid rgba(0, 0, 0, 0.05);
        transition: 0.3s ease-in-out; z-index: 99;
    }

    .sidebar-item {
        background: white; padding: 25px 10px; text-align: center; border-bottom: 1px solid #eee;
        text-decoration: none; color: #333; display: block; transition: all 0.3s ease;
    }
    .sidebar-item:hover { background: #FDFDFD; transform: translateX(5px); }
    .sidebar-item i { font-size: 32px; display: block; margin-bottom: 8px; color: #000; }
    .sidebar-item span { font-weight: bold; font-size: 13px; }

    
    .content-area {
        flex-grow: 1; padding: 40px; display: flex; flex-direction: column;
    }

    
    .search-wrap { display: flex; justify-content: flex-end; margin-bottom: 10px; }
    .search-input-group {
        background: #D9D9D9; border-radius: 10px; padding: 5px 15px;
        display: flex; align-items: center; width: 220px;
    }
    .search-input-group input {
        border: none; background: transparent; font-size: 14px; outline: none; width: 100%;
    }

    
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .custom-table th { background: var(--table-header); padding: 12px; text-align: center; font-size: 14px; border: none; font-weight: bold;}
    .custom-table td { background: var(--table-row); padding: 12px; text-align: center; font-size: 14px; border: none; vertical-align: middle; }
    
    .custom-table tr td:first-child, .custom-table tr th:first-child { border-radius: 8px 0 0 8px; }
    .custom-table tr td:last-child, .custom-table tr th:last-child { border-radius: 0 8px 8px 0; }

    .pdf-icon { width: 32px; cursor: pointer; transition: transform 0.2s; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.1)); }
    .pdf-icon:hover { transform: scale(1.15); }

    .pagination-bar {
        background: #D9D9D9; padding: 15px; border-radius: 8px;
        display: flex; justify-content: center; gap: 15px; margin-top: auto;
    }

    .btn-purple {
        background-color: var(--btn-blue); color: white; border: none;
        border-radius: 20px; padding: 6px 30px; font-size: 14px; transition: 0.3s; font-weight: 500;
    }
    .btn-purple:hover:not(:disabled) { background-color: #4f46b5; transform: translateY(-1px); }
    .btn-purple:disabled { opacity: 0.5; cursor: not-allowed; }

    
    @media (max-width: 768px) {
        .sidebar { position: absolute; top: 0; left: -230px; height: 100%; box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1); }
        .sidebar.active { left: 0; }
        .top-navbar { padding: 10px 15px; }
        .brand-name { font-size: 18px; }
        .content-area { padding: 20px 10px; }
        .logout-text { padding: 2px !important; font-size: 10px !important; }
        .search-input-group { width: 100%; } 
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn" style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/60x60?text=Logo'">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">รายงาน E-portfolio</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
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
                <a href="admin_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="admin_e-portfolio.php" class="sidebar-item mb-3" style="background: #FDFDFD;">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'academic_officer'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                     <i class="fa-solid fa-users"></i>
                   <span>ข้อมูลสมาชิกสโมสร / นายกสโมสร / รองนายกสโมสร </span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                     <i class="fa-solid fa-users"></i>
                   <span>ข้อมูลสมาชิกสโมสร</span>
                </a>
                <?php endif; ?>

                <a href="admin_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-cubes"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_score_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>ข้อมูลการเข้าร่วมกิจกรรม</span>
                </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], ['academic_officer', 'club_president'])): ?>
                <a href="admin_transcript.php" class="sidebar-item">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Transcript</span>
                </a>
                <?php endif; ?>

                <div class="px-3 mt-4">
                    <select id="filterAcademicYear" class="form-select form-select-sm mb-2" style="font-size: 13px;">
                        <option value="">ทุกปีการศึกษา</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>">ปีการศึกษา <?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterDepartment" class="form-select form-select-sm mb-2" style="font-size: 13px;">
                        <option value="">ทุกสาขาวิชา</option>
                        <?php 
                            $depts = ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"];
                            foreach ($depts as $dept) echo "<option value='$dept'>$dept</option>";
                        ?>
                    </select>
                </div>
            </aside>

            <main class="content-area">
                <div class="search-wrap w-100">
                    <div class="search-input-group">
                        <i class="fa fa-search me-2" style="font-size: 14px; color: #666;"></i>
                        <input type="text" class="search-name" placeholder="ค้นหาชื่อหรือรหัส...">
                    </div>
                </div>

                <div class="table-responsive w-100" style="min-height: 400px;">
                    <table class="custom-table" id="memberTable">
                        <thead>
                            <tr>
                                <th width="45%" class="text-start ps-4">ชื่อ-นามสกุล</th>
                                <th width="35%">สาขาวิชา</th>
                                <th width="20%">E-Portfolio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="member-row" 
                                        data-year="<?= htmlspecialchars($row['academic_year']) ?>" 
                                        data-dept="<?= htmlspecialchars($row['department']) ?>">
                                        <td class="text-start ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($row['idstudent']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                                        <td>
                                            <a href="admin_detail_E-portfolio.php?user_id=<?= $row['user_id'] ?>&action=portfolio" title="ดูแฟ้มสะสมผลงาน">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/8/87/PDF_file_icon.svg" class="pdf-icon" alt="PDF">
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                            
                            <tr id="noDataRow" style="display: <?= ($result->num_rows > 0) ? 'none' : 'table-row' ?>;">
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open mb-2" style="font-size: 24px; color:#ccc;"></i><br>
                                    ไม่พบข้อมูลที่ค้นหา
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-bar w-100 mt-4">
                    <button type="button" id="btnPrev" class="btn-purple"><i class="fa-solid fa-chevron-left me-1"></i> กลับ</button>
                    <span id="pageIndicator" style="align-self: center; font-weight: bold; font-size: 14px; color: #555;">หน้า 1 / 1</span>
                    <button type="button" id="btnNext" class="btn-purple">ถัดไป <i class="fa-solid fa-chevron-right ms-1"></i></button>
                </div>
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

        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        // -------------------------------------------------------------
        // ระบบค้นหา + ตัวกรอง + Pagination แบบรวมศูนย์
        // -------------------------------------------------------------
        let currentPage = 1;
        const rowsPerPage = 10;
        let filteredRows = [];

        function updateTable() {
            const searchTerm = $(".search-name").val().toLowerCase();
            const academicYear = $("#filterAcademicYear").val();
            const department = $("#filterDepartment").val();

            filteredRows = [];

            // ตรวจสอบเงื่อนไขทุกแถว
            $(".member-row").each(function() {
                const rowText = $(this).text().toLowerCase();
                const rowYear = String($(this).data("year") || '');
                const rowDept = $(this).data("dept") || '';

                const matchSearch = (searchTerm === "" || rowText.indexOf(searchTerm) > -1);
                const matchYear = (academicYear === "" || rowYear === academicYear);
                const matchDept = (department === "" || rowDept === department);

                if (matchSearch && matchYear && matchDept) {
                    filteredRows.push(this);
                }
                $(this).hide(); // ซ่อนทั้งหมดไว้ก่อน
            });

            // กลับไปหน้า 1 เสมอเมื่อมีการค้นหาหรือเปลี่ยนตัวกรอง
            displayPage(1);
        }

        function displayPage(page) {
            let totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (totalPages === 0) totalPages = 1;

            currentPage = page;
            if (currentPage < 1) currentPage = 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // แสดงเฉพาะแถวในหน้าที่เลือก
            $(".member-row").hide();
            $(filteredRows).slice(start, end).show();

            // จัดการข้อความหน้า
            $("#pageIndicator").text(`หน้า ${currentPage} / ${totalPages}`);

            // จัดการปุ่ม
            $("#btnPrev").prop("disabled", currentPage === 1);
            $("#btnNext").prop("disabled", currentPage === totalPages || filteredRows.length === 0);

            // แสดงคำว่าไม่พบข้อมูล
            if (filteredRows.length === 0) {
                $("#noDataRow").show();
            } else {
                $("#noDataRow").hide();
            }
        }

        // จับ Event ปุ่มเปลี่ยนหน้า
        $("#btnPrev").click(function() { displayPage(currentPage - 1); });
        $("#btnNext").click(function() { displayPage(currentPage + 1); });

        // จับ Event การค้นหาและเปลี่ยนตัวกรอง
        $(".search-name").on("keyup", updateTable);
        $("#filterAcademicYear, #filterDepartment").on("change", updateTable);

        // โหลดข้อมูลครั้งแรก
        updateTable();
    });
    </script>
</body>
</html>