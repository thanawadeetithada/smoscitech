<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// --- ดึงข้อมูลรูปโปรไฟล์สำหรับ Top Navbar ---
$user_id_session = $_SESSION['user_id'];
$stmt_profile_nav = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile_nav->bind_param("i", $user_id_session);
$stmt_profile_nav->execute();
$res_profile_nav = $stmt_profile_nav->get_result();
$user_data_nav = $res_profile_nav->fetch_assoc();
// ถ้าไม่มีรูปให้ใช้ default.png
$profile_image_nav = !empty($user_data_nav['profile_image']) ? $user_data_nav['profile_image'] : 'default.png';
$stmt_profile_nav->close();
// ---------------------------------------------------------

$error_message = "";
$registration_success = false;
$profile_image = "default.png";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $idstudent = $_POST['idstudent'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // เพิ่มรับค่า phone
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $academic_year = $_POST['academic_year'];
    $year_level = $_POST['year_level'];
    $department = $_POST['department'];
    $userrole = $_POST['userrole'];
    
    if (isset($_POST['existing_profile_image'])) {
        $profile_image = $_POST['existing_profile_image'];
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["profile_image"]["name"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!array_key_exists(strtolower($ext), $allowed)) {
            $error_message = "กรุณาเลือกไฟล์รูปภาพที่ถูกต้อง (jpg, jpeg, png)";
        }

        $maxsize = 2 * 1024 * 1024;
        if ($_FILES["profile_image"]["size"] > $maxsize) {
            $error_message = "ขนาดรูปภาพต้องไม่เกิน 2MB";
        }

        if (empty($error_message)) {
            $new_filename = $idstudent . "_" . time() . "." . $ext;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], "uploads/" . $new_filename)) {
                if ($profile_image != "default.png" && file_exists("uploads/" . $profile_image)) {
                    unlink("uploads/" . $profile_image);
                }
                $profile_image = $new_filename;
            }
        }
    }

    if ($password !== $confirmpassword) {
        $error_message = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    }

    if (empty($error_message)) {
        $check_duplicate_sql = "SELECT email, idstudent 
                        FROM users 
                        WHERE (email = ? OR idstudent = ?) 
                        AND deleted_at IS NULL
                        LIMIT 1";
        if ($stmt = $conn->prepare($check_duplicate_sql)) {
            $stmt->bind_param("ss", $email, $idstudent);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($existing_email, $existing_idstudent);
                $stmt->fetch();
                if ($email === $existing_email && $idstudent === $existing_idstudent) {
                    $error_message = "อีเมลและชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว";
                } elseif ($email === $existing_email) {
                    $error_message = "อีเมลนี้ถูกใช้ไปแล้ว กรุณาใช้อีเมลอื่น";
                } elseif ($idstudent === $existing_idstudent) {
                    $error_message = "ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว กรุณาใช้ชื่อผู้ใช้งานอื่น";
                }
            }
            $stmt->close();
        } else {
            $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่งตรวจสอบซ้ำ";
        }
    }

    if (empty($error_message)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // เพิ่ม phone ลงใน SQL
        $sql = "INSERT INTO users (first_name, last_name, email, idstudent, password, academic_year, year_level, department, profile_image, userrole, phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $conn->prepare($sql);
            // เพิ่ม 's' สำหรับ phone รวมเป็น 11 ตัว และใส่ตัวแปร $phone
            $stmt->bind_param("sssssssssss", $first_name, $last_name, $email, $idstudent, $hashed_password, $academic_year, $year_level, $department, $profile_image, $userrole, $phone);
            $stmt->execute();
            $registration_success = true;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 $error_message = "ชื่อผู้ใช้งานหรืออีเมลนี้ถูกใช้ไปแล้ว";
            } else {
                 $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#A37E5E">
    <title>เพิ่มข้อมูลผู้ใช้งาน - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F8F9FC;
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
        padding: 40px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    
    .form-container {
        background: #ffffff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        width: 100%;
        max-width: 800px;
        border: 1px solid #eee;
    }

    .form-title {
        color: #333;
        font-weight: bold;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
    }

    .form-title::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background-color: var(--top-bar-bg);
        margin: 10px auto 0;
        border-radius: 2px;
    }

    .form-label {
        font-weight: bold;
        color: #444;
        margin-bottom: 8px;
    }

    .form-control,
    .form-select {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px 15px;
        transition: all 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--top-bar-bg);
        box-shadow: 0 0 0 0.2rem rgba(163, 126, 94, 0.25);
    }

    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
    }

    .btn-cancel {
        background-color: #e0e0e0;
        color: #333;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-cancel:hover {
        background-color: #c8c8c8;
        color: #000;
    }

    
    .bg-purple-modal {
        background-color: var(--btn-blue) !important;
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
        }

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
        }

        .form-container {
            padding: 20px;
        }

        .btn-group-responsive {
            flex-direction: column;
            gap: 10px;
        }

        .btn-group-responsive button {
            width: 100%;
        }
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
                    <span class="text-page-pill-btn mt-1">เพิ่มข้อมูลผู้ใช้งาน</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image_nav); ?>" alt="Profile"
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
                <a href="admin_e-portfolio.php" class="sidebar-item mb-3">
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
            </aside>

            <main class="content-area">
                <div class="form-container">
                    <h3 class="form-title">เพิ่มข้อมูลสมาชิก/ผู้ใช้งาน</h3>

                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger text-center shadow-sm" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">ชื่อ <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="first_name" name="first_name" placeholder="ระบุชื่อ" required
                                    class="form-control"
                                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">นามสกุล <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="last_name" name="last_name" placeholder="ระบุนามสกุล" required
                                    class="form-control"
                                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="idstudent" class="form-label">รหัสนักศึกษา <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="idstudent" name="idstudent" placeholder="ระบุรหัสนักศึกษา"
                                    required class="form-control"
                                    value="<?php echo isset($_POST['idstudent']) ? htmlspecialchars($_POST['idstudent']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์ <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="phone" name="phone" placeholder="ระบุเบอร์โทรศัพท์ 10 หลัก"
                                    required class="form-control"
                                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                    maxlength="10">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" placeholder="example@email.com" required
                                class="form-control"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="academic_year" class="form-label">ปีการศึกษา <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="academic_year" class="form-control" placeholder="เช่น 2567"
                                    required value="<?php echo @htmlspecialchars($_POST['academic_year']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="year_level" class="form-label">ชั้นปี <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="" disabled
                                        <?php echo !isset($_POST['year_level']) ? 'selected' : ''; ?>>-- เลือกชั้นปี --
                                    </option>
                                    <?php
                                    $year_levels = ["ชั้นปีที่ 1", "ชั้นปีที่ 2", "ชั้นปีที่ 3", "ชั้นปีที่ 4", "อื่นๆ"];
                                    foreach ($year_levels as $yl) {
                                        $selected = (isset($_POST['year_level']) && $_POST['year_level'] == $yl) ? 'selected' : '';
                                        echo "<option value=\"$yl\" $selected>$yl</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="userrole" class="form-label">สิทธิ์การใช้งาน <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="userrole" name="userrole" required>
                                    <option value="">-- เลือกประเภท --</option>
                                    <option value="executive"
                                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'executive') ? 'selected' : ''; ?>>
                                        ผู้บริหาร</option>
                                    <option value="academic_officer"
                                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'academic_officer') ? 'selected' : ''; ?>>
                                        นักวิชาการศึกษา</option>
                                    <option value="club_president"
                                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'club_president') ? 'selected' : ''; ?>>
                                        นายก/รองนายกสโมสรฯ</option>
                                    <option value="club_member"
                                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'club_member') ? 'selected' : ''; ?>>
                                        สมาชิกสโมสรนักศึกษาฯ</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label">สาขาวิชา <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="" disabled <?php echo !isset($_POST['department']) ? 'selected' : ''; ?>>
                                    -- เลือกสาขาวิชา --</option>
                                <?php
                                $depts = ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"];
                                foreach ($depts as $d) {
                                    $selected = (isset($_POST['department']) && $_POST['department'] == $d) ? 'selected' : '';
                                    echo "<option value=\"$d\" $selected>$d</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">รหัสผ่าน <span
                                        class="text-danger">*</span></label>
                                <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required
                                    class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="confirmpassword" class="form-label">ยืนยันรหัสผ่าน <span
                                        class="text-danger">*</span></label>
                                <input type="password" id="confirmpassword" name="confirmpassword"
                                    placeholder="ยืนยันรหัสผ่านอีกครั้ง" required class="form-control">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="profile_image" class="form-label">รูปโปรไฟล์</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-control"
                                accept="image/*" onchange="previewImage(event)">
                            <input type="hidden" name="existing_profile_image"
                                value="<?php echo htmlspecialchars($profile_image); ?>">

                            <div class="mt-4 text-center">
                                <?php 
                                $display_img = "bg/default-profile.png";
                                $preview_style = "display: none;";
                                if ($profile_image != "default.png" && file_exists("uploads/" . $profile_image)) {
                                    $display_img = "uploads/" . $profile_image;
                                    $preview_style = "display: inline-block;";
                                }
                                ?>
                                <img id="preview" src="<?php echo $display_img; ?>" alt="Preview" class="shadow-sm"
                                    style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid var(--top-bar-bg); <?php echo $preview_style; ?>">
                            </div>
                        </div>

                        <hr style="opacity: 0.1;" class="mb-4">

                        <div class="d-flex justify-content-center btn-group-responsive gap-3">
                            <button type="submit" class="btn btn-purple-custom px-5"><i
                                    class="fa-solid fa-floppy-disk me-2"></i> บันทึกข้อมูล</button>
                            <button type="button" onclick="window.location.href='admin_user_management.php'"
                                class="btn btn-cancel px-5"><i class="fa-solid fa-xmark me-2"></i> ยกเลิก</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-purple-modal text-white border-0">
                    <h5 class="modal-title fw-bold">ระบบจัดการผู้ใช้งาน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4" id="statusMessage" style="font-size: 16px;">
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-purple-custom px-4" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
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

        // ปิด Sidebar หากคลิกพื้นที่อื่นบนหน้าจอ (มือถือ)
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn')
                    .length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const isRegistrationSuccess = <?php echo $registration_success ? 'true' : 'false'; ?>;

        if (isRegistrationSuccess) {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-4x mb-3'></i><br><span class='fw-bold text-dark'>เพิ่มข้อมูลผู้ใช้งานเรียบร้อยแล้ว</span>"
            );
            $("#statusModal").modal("show");
        } else if (status === 'success') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-4x mb-3'></i><br><span class='fw-bold text-dark'>อัปเดตข้อมูลเรียบร้อยแล้ว</span>"
            );
            $("#statusModal").modal("show");
        } else if (status === 'error') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-triangle-exclamation text-danger fa-4x mb-3'></i><br><span class='fw-bold text-dark'>เกิดข้อผิดพลาดในการดำเนินการ</span>"
            );
            $("#statusModal").modal("show");
        }

        $("#statusModal").on('hidden.bs.modal', function() {
            if (isRegistrationSuccess || status === 'success') {
                window.location.href = 'admin_user_management.php';
            }
        });
    });

    function previewImage(event) {
        const reader = new FileReader();
        const imageField = document.getElementById("preview");

        reader.onload = function() {
            if (reader.readyState === 2) {
                imageField.src = reader.result;
                imageField.style.display = "inline-block";
            }
        }

        if (event.target.files && event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            imageField.src = "bg/default-profile.png";
            imageField.style.display = "none";
        }
    }
    </script>
</body>

</html>