<?php
session_start();
ob_start();
require 'db.php';

$modal_message = $_SESSION['modal_message'] ?? '';
$modal_type = $_SESSION['modal_type'] ?? '';
unset($_SESSION['modal_message'], $_SESSION['modal_type']);

$error_message = "";
$idstudentOrEmail = "";
$show_approval_modal = false; 

if (!empty($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === $_SERVER['HTTP_HOST']) {
    $referer_page = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
    if (!in_array($referer_page, ["index.php", "reset_password.php", "process_reset_password.php"])) {
        $_SESSION['redirect_to'] = $_SERVER['HTTP_REFERER'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idstudentOrEmail = trim($_POST['idstudent'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($idstudentOrEmail === "" || $password === "") {
        $error_message = "⚠️ กรุณากรอกข้อมูลให้ครบถ้วน!";
    } else {
        $sql = "SELECT * FROM users WHERE (idstudent = ? OR email = ?) AND deleted_at IS NULL LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $idstudentOrEmail, $idstudentOrEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    
                    if ($user['membership_status'] === 'no_member') {
                        $show_approval_modal = true;
                    } else if ($user['membership_status'] === 'member') {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['idstudent'] = $user['idstudent'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['academic_year'] = $user['academic_year'];
                        $_SESSION['year_level'] = $user['year_level'];
                        $_SESSION['department'] = $user['department'];
                        $_SESSION['userrole'] = $user['userrole'];
                        $_SESSION['membership_status'] = $user['membership_status'];
                        
                        switch ($user['userrole']) {
                            case 'executive':
                            case 'academic_officer':
                            case 'club_president':
                                $redirect_page = "admin_report_activity.php";
                                break;
                            case 'club_member':
                            default:
                                $redirect_page = "report_activity.php";
                                break;
                        }
                        header("Location: $redirect_page");
                        exit();
                    } else {
                        $error_message = "⚠️ บัญชีของคุณมีสถานะไม่ถูกต้อง กรุณาติดต่อผู้ดูแลระบบ";
                    }
                } else {
                    $error_message = "❌ รหัสผ่านไม่ถูกต้อง!";
                }
            } else {
                 $error_message = "⚠️ ไม่มีผู้ใช้งานหรืออีเมลนี้อยู่ในระบบ หรือบัญชีถูกลบแล้ว!";
            }
            $stmt->close();
        } else {
            $error_message = "⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล!";
        }
    }
}
$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>เข้าสู่ระบบ</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&family=Prompt:wght@400;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --sidebar-bg: #FEEFB3;
        --body-bg: #F4F4F4;
        --card-bg: #FEE799;
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
        align-items: center;
        padding: 20px;
    }

    
    .login-card {
        background-color: var(--card-bg);
        width: 100%;
        max-width: 400px;
        padding: 40px 35px;
        border-radius: 35px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        text-align: center;
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

    .login-card h2 {
        font-family: serif;
        margin-bottom: 30px;
        color: #444;
        font-size: 26px;
    }

    .form-control-custom {
        border: 1px solid transparent;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 18px;
        background: white;
        box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: 0.3s;
    }

    .form-control-custom:focus {
        box-shadow: 0 0 0 3px rgba(99, 88, 225, 0.2);
        border-color: var(--btn-login);
        outline: none;
    }

    .btn-submit {
        background-color: var(--btn-login);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 50px;
        font-size: 18px;
        font-weight: 600;
        transition: 0.3s;
        box-shadow: 0 4px 15px rgba(99, 88, 225, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 88, 225, 0.4);
        opacity: 0.95;
    }

    .footer-links {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        font-size: 14px;
    }

    .footer-links a {
        color: #666;
        text-decoration: none;
        transition: 0.2s;
    }

    .footer-links a:hover {
        color: var(--btn-login);
        text-decoration: underline;
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

        .login-card {
            padding: 30px 20px;
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
                <a href="register.php" style="text-decoration: none;">
                    <i class="fa-solid fa-circle-user ms-3" style="font-size: 40px; color: #333;"></i>
                </a>
            </div>
        </nav>

        <div class="main-container">
            <aside class="sidebar">
                <a href="main_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="main_e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>
                <div style="flex:1;"></div>
            </aside>

            <main class="content">
                <div class="login-card">
                    <h2>SMO SCITECH KPRU</h2>

                    <?php if ($error_message): ?>
                    <div class="alert alert-danger error-box py-2 mb-3" style="border-radius: 10px; font-size: 14px;">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST">
                        <input type="text" name="idstudent" class="form-control form-control-custom"
                            placeholder="รหัสนักศึกษา" value="<?php echo htmlspecialchars($idstudentOrEmail); ?>"
                            required>

                        <input type="password" name="password" class="form-control form-control-custom"
                            placeholder="รหัสผ่าน" required>

                        <button type="submit" class="btn-submit">Login</button>
                    </form>

                    <div class="footer-links">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">ลืมรหัสผ่าน</a>
                        <a href="register.php">สมัครสมาชิก</a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 350px;">
            <div class="modal-content text-center p-4"
                style="border-radius: 35px; border: none; background-color: var(--sidebar-bg); box-shadow: 0 10px 30px rgba(0,0,0,0.15);align-items: center;">
                <h5 class="mb-4" style="font-weight: bold; color: #333; font-size: 20px;">ลืมรหัสผ่าน</h5>

                <div class="mb-4">
                    <i class="fa-solid fa-unlock" style="font-size: 50px; color: #000;"></i>
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        <div style="display: flex; flex-direction: column; align-items: center; width: 22px;">
                            <span style="font-size: 40px; font-weight: bold; line-height: 1; margin-bottom: -15px;">*</span>
                            <span style="border-bottom: 3px solid #000; width: 100%;"></span>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; width: 22px;">
                            <span style="font-size: 40px; font-weight: bold; line-height: 1; margin-bottom: -15px;">*</span>
                            <span style="border-bottom: 3px solid #000; width: 100%;"></span>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; width: 22px;">
                            <span style="font-size: 40px; font-weight: bold; line-height: 1; margin-bottom: -15px;">*</span>
                            <span style="border-bottom: 3px solid #000; width: 100%;"></span>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; width: 22px;">
                            <span style="font-size: 40px; font-weight: bold; line-height: 1; margin-bottom: -15px;">*</span>
                            <span style="border-bottom: 3px solid #000; width: 100%;"></span>
                        </div>
                    </div>
                </div>

                <input type="email" id="forgotEmail" class="form-control mb-3 mx-auto"
                    style="border-radius: 8px; background-color: #EEF0F8; border: none; padding: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 95%;"
                    placeholder="">

                <button type="button" class="btn py-2 mb-4" id="sendLinkBtn"
                    style="background-color: var(--btn-login); color: white; border-radius: 8px; font-weight: bold; width: 60%;">รีเซ็ท</button>

                <p class="text-muted small mb-0" style="font-size: 13px; color: #666 !important;">
                    กรุณากรอกอีเมลเพื่อรีเซ็ทรหัสผ่านของคุณ</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 350px;">
            <div class="modal-content text-center p-4"
                style="border-radius: 35px; border: none; background-color: var(--sidebar-bg); box-shadow: 0 10px 30px rgba(0,0,0,0.15); align-items: center;">
                <h5 class="mb-4" style="font-weight: bold; color: #333; font-size: 20px;">แจ้งเตือนระบบ</h5>

                <div class="mb-3">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 50px; color: #F5A623;"></i>
                </div>

                <p class="mb-4 mt-2" style="font-size: 16px; color: #444;">
                    บัญชีของคุณอยู่ระหว่าง<br><b style="color: #F5A623; font-size: 18px;">รอทำการอนุมัติ</b><br>กรุณาติดต่อผู้ดูแลระบบ
                </p>

                <button type="button" class="btn py-2" data-bs-dismiss="modal"
                    style="background-color: var(--btn-login); color: white; border-radius: 8px; font-weight: bold; width: 60%;">ตกลง</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // ตรวจสอบเงื่อนไขจาก PHP เพื่อแสดง Modal อนุมัติ
        <?php if ($show_approval_modal): ?>
            $('#approvalModal').modal('show');
        <?php endif; ?>

        // Toggle Sidebar สำหรับมือถือ
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        // ปิด Sidebar หากคลิกพื้นที่อื่นบนหน้าจอ (เฉพาะในหน้าจอมือถือ)
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        $('#sendLinkBtn').on('click', function() {
            const email = $('#forgotEmail').val().trim();
            if (!email) {
                alert('กรุณากรอกอีเมล');
                return;
            }

            $.post('process_forgot_password.php', {
                email: email
            }, function(res) {
                alert(res.message);
                if (res.status === 'success') $('#forgotPasswordModal').modal('hide');
            }, 'json').fail(function() {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อระบบ');
            });
        });
    });
    </script>

</body>

</html>