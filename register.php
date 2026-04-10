<?php
require 'db.php';
$error_message = "";
$registration_success = false;
$profile_image = "default.png";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $idstudent = $_POST['idstudent'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $academic_year = $_POST['academic_year'];
    $department = $_POST['department'];
    $year_level = $_POST['year_level'];
    
    // กำหนดค่าเริ่มต้นสำหรับสมาชิกใหม่
    $userrole = 'club_member'; 
    $membership_status = 'no_member'; 

    $profile_image = isset($_POST['existing_profile_image']) ? $_POST['existing_profile_image'] : "default.png";

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["profile_image"]["name"];
        $filesize = $_FILES["profile_image"]["size"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (array_key_exists(strtolower($ext), $allowed) && $filesize <= 2 * 1024 * 1024) {
            $new_filename = $idstudent . "_" . time() . "." . $ext;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], "uploads/" . $new_filename)) {
                $profile_image = $new_filename;
            }
        } else {
            $error_message = "ไฟล์ไม่ถูกต้องหรือขนาดเกิน 2MB";
        }
    }

    if ($password !== $confirmpassword) { $error_message = "รหัสผ่านไม่ตรงกัน"; }

    if (empty($error_message)) {
        $check = $conn->prepare("SELECT user_id FROM users WHERE (email = ? OR idstudent = ?) AND deleted_at IS NULL LIMIT 1");
        $check->bind_param("ss", $email, $idstudent);
        $check->execute();
        if ($check->get_result()->num_rows > 0) { $error_message = "รหัสนักศึกษาหรืออีเมลนี้ถูกใช้ไปแล้ว"; }
        $check->close();
    }

    if (empty($error_message)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. แก้ไข SQL: เพิ่มคอลัมน์ phone และจัดลำดับ ? ให้ครบ 12 ตัว
        $sql = "INSERT INTO users (first_name, last_name, email, idstudent, phone, password, academic_year, year_level, department, profile_image, userrole, membership_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // 3. แก้ไข bind_param: เปลี่ยนเป็น "ssssssssssss" (s ทั้งหมด 12 ตัว) และใส่ตัวแปรให้ครบตามลำดับ SQL
        $stmt->bind_param("ssssssssssss", 
            $first_name, 
            $last_name, 
            $email, 
            $idstudent, 
            $phone, 
            $hashed, 
            $academic_year, 
            $year_level, 
            $department, 
            $profile_image, 
            $userrole, 
            $membership_status
        );
        
        if ($stmt->execute()) { $registration_success = true; }
        $stmt->close();
    }
    $conn->close();
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
    <title>สมัครสมาชิก</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --sidebar-bg: #FEEFB3;
        --body-bg: #F4F4F4;
        --btn-gold: #E6A129;
        --btn-login: #6358E1;
    }

    body,
    html {
        height: 100%;
        margin: 0;
        font-family: 'Sarabun', sans-serif;
        background-color: var(--body-bg);
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
        font-size: clamp(16px, 4vw, 22px);
        font-family: serif;
        letter-spacing: 0.5px;
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

    .main-container {
        display: flex;
        flex: 1;
        position: relative;
    }

    .sidebar {
        width: 240px;
        background-color: var(--sidebar-bg);
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
        transition: 0.3s ease-in-out;
        z-index: 99;
    }

    .sidebar-item {
        background: white;
        padding: 25px 15px;
        text-align: center;
        border-bottom: 1px solid #eee;
        text-decoration: none;
        color: #333;
        transition: all 0.3s ease;
    }

    .sidebar-item:hover {
        background: #FDFDFD;
        transform: translateX(5px);
    }

    .sidebar-item i {
        font-size: 32px;
        margin-bottom: 8px;
        display: block;
    }

    .sidebar-item span {
        font-size: 13px;
        font-weight: 700;
    }

    .content {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 30px 20px;
    }

    .register-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 850px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-title {
        text-align: center;
        margin-bottom: 30px;
    }

    .form-title h2 {
        font-size: 26px;
        font-weight: bold;
        color: #333;
        font-family: serif;
    }

    .input-group-custom {
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        padding: 4px 15px;
        margin-bottom: 15px;
        border: 1px solid #eee;
        transition: 0.3s;
    }

    .input-group-custom:focus-within {
        border-color: var(--btn-gold);
        box-shadow: 0 0 0 3px rgba(230, 161, 41, 0.2);
    }

    .input-group-custom i {
        color: var(--btn-gold);
        font-size: 18px;
        width: 30px;
        text-align: center;
    }

    .input-group-custom input,
    .input-group-custom select {
        border: none;
        padding: 10px;
        flex-grow: 1;
        outline: none;
        background: transparent;
        font-size: 15px;
        width: 100%;
    }

    .btn-register {
        background-color: var(--btn-gold);
        color: white;
        border: none;
        padding: 12px 50px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 18px;
        width: auto;
        margin-top: 15px;
        transition: 0.3s;
        box-shadow: 0 4px 15px rgba(230, 161, 41, 0.3);
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(230, 161, 41, 0.4);
        opacity: 0.95;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            top: 0;
            left: -240px;
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

        .content {
            padding: 15px 10px;
        }

        .register-card {
            padding: 20px;
            border-radius: 15px;
        }

        .form-title h2 {
            font-size: 22px;
        }

        .btn-register {
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
                <span class="brand-name">SMO SCITECH KPRU</span>
            </div>
            <div class="d-flex align-items-center">
                <a href="index.php" class="login-pill-btn d-none d-sm-block">Login</a>
                <a href="index.php" style="text-decoration: none;">
                    <i class="fa-solid fa-circle-user ms-3" style="font-size: 40px; color: #333;"></i>
                </a>
            </div>
        </nav>

        <div class="main-container">
            <aside class="sidebar">
                <a href="main_report_activity.php" class="sidebar-item mb-3 mt-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="main_e-portfolio.php" class="sidebar-item">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>
                <div style="flex:1;"></div>
            </aside>

            <main class="content">
                <div class="register-card">
                    <div class="form-title">
                        <h2>สมัครสมาชิกสโมสรนักศึกษา</h2>
                        <p class="text-muted small">กรุณากรอกข้อมูลด้านล่างให้ครบถ้วน</p>
                    </div>

                    <?php if($error_message): ?>
                    <div class="alert alert-danger py-2 small text-center"><?= $error_message ?></div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-2">
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-user"></i><input type="text"
                                        name="first_name" placeholder="ชื่อจริง" required
                                        value="<?= @$_POST['first_name'] ?>"></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-user"></i><input type="text"
                                        name="last_name" placeholder="นามสกุล" required
                                        value="<?= @$_POST['last_name'] ?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-id-card"></i><input type="text"
                                        name="idstudent" placeholder="รหัสนักศึกษา" required
                                        value="<?= @$_POST['idstudent'] ?>"></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-university"></i>
                                    <select name="department" required>
                                        <option value="" disabled selected>สาขาวิชา</option>
                                        <option value="วิทยาการคอมพิวเตอร์">วิทยาการคอมพิวเตอร์</option>
                                        <option value="เทคโนโลยีสารสนเทศ">เทคโนโลยีสารสนเทศ</option>
                                        <option value="นวัตกรรมและธุรกิจอาหาร">นวัตกรรมและธุรกิจอาหาร</option>
                                        <option value="สาธารณสุขศาสตร์">สาธารณสุขศาสตร์</option>
                                        <option value="เคมี (วท.บ.)">เคมี (วท.บ.)</option>
                                        <option value="วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม">
                                            วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม</option>
                                        <option value="ฟิสิกส์">ฟิสิกส์</option>
                                        <option value="เคมี (ค.บ.)">เคมี (ค.บ.)</option>
                                        <option value="ชีววิทยา">ชีววิทยา</option>
                                        <option value="คณิตศาสตร์ประยุกต์">คณิตศาสตร์ประยุกต์</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-phone"></i><input type="text"
                                        name="phone" placeholder="เบอร์โทรศัพท์" value="<?= @$_POST['phone'] ?>"></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-graduation-cap"></i>
                                    <select name="year_level" required>
                                        <option value="" disabled selected>ชั้นปี</option>
                                        <option value="ชั้นปีที่ 1">ปี 1</option>
                                        <option value="ชั้นปีที่ 2">ปี 2</option>
                                        <option value="ชั้นปีที่ 3">ปี 3</option>
                                        <option value="ชั้นปีที่ 4">ปี 4</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-lock"></i><input type="password"
                                        name="password" placeholder="รหัสผ่าน" required></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-lock"></i><input type="password"
                                        name="confirmpassword" placeholder="ยืนยันรหัสผ่าน" required></div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-calendar"></i><input type="number"
                                        name="academic_year" placeholder="ปีการศึกษา" required
                                        value="<?= date('Y')+543 ?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="input-group-custom"><i class="fa fa-envelope"></i><input type="email"
                                        name="email" placeholder="อีเมล" required></div>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted mb-1 ms-2">รูปโปรไฟล์ (ไม่บังคับ)</label>
                                <div class="input-group-custom"><i class="fa fa-image"></i><input type="file"
                                        name="profile_image" accept="image/*" required></div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn-register">สมัครสมาชิก</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="font-family: 'Prompt', sans-serif;">
                <div class="modal-header text-white" style="background-color: var(--top-bar-bg);">
                    <h5 class="modal-title fw-bold" id="statusModalLabel">แจ้งเตือนระบบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class='fa-solid fa-circle-check text-success fa-3x mb-3'></i>
                    <h4 class='text-success fw-bold'>สำเร็จ</h4>
                    <p class='text-muted mb-0'>สมัครสมาชิกเรียบร้อยแล้ว<br>กรุณารอการอนุมัติจากผู้ดูแลระบบเพื่อเข้าใช้งาน</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn text-white px-5" style="background-color: var(--top-bar-bg); border-radius: 8px;" onclick="window.location.href='index.php'">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn')
                    .length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        <?php if ($registration_success): ?>
        new bootstrap.Modal(document.getElementById('statusModal')).show();
        <?php endif; ?>
    });
    </script>
</body>

</html>