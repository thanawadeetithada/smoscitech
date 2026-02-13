<?php
session_start();
ob_start();
require 'db.php';

$modal_message = $_SESSION['modal_message'] ?? '';
$modal_type = $_SESSION['modal_type'] ?? '';
unset($_SESSION['modal_message'], $_SESSION['modal_type']);

$error_message = "";
$usernameOrEmail = "";

if (!empty($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === $_SERVER['HTTP_HOST']) {
    $referer_page = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
    if (!in_array($referer_page, ["index.php", "reset_password.php", "process_reset_password.php"])) {
        $_SESSION['redirect_to'] = $_SERVER['HTTP_REFERER'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameOrEmail === "" || $password === "") {
        $error_message = "⚠️ กรุณากรอกข้อมูลให้ครบถ้วน!";
    } else {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['userrole'] = $user['userrole'];

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
    body {
        font-family: 'Prompt', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: url('bg/sky.png') no-repeat center center/cover;
        margin: 0;
        background: #cfd8e5;
    }

    .container {
        background: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        text-align: center;
        margin: 30px;
        border: 1px solid #ccc;
    }

    h2 {
        margin-bottom: 20px;
        color: black;
    }

    .alert {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
        font-size: 14px;
        text-align: center;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    form {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    label {
        text-align: left;
        font-weight: bold;
        margin-top: 10px;
        margin-bottom: 0px;
    }

    input {
         margin: 5px 0;
        /* width: 100%;
        padding: 12px;
        margin: 5px 0;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px; */
    }

    .input-email {
        width: 90%;
        padding: 8px;
        margin: 5px auto;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
        display: block;
    }


    button {
        width: 100%;
        padding: 8px;
        background: #8c99bc;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 15px;
    }

    a {
        display: block;
        text-align: center;
        margin-top: 10px;
        color: #007BFF;
        text-decoration: none;
        font-weight: bold;
    }

    a:hover {
        text-decoration: underline;
    }

    .btn-send-password {
        display: flex;
        justify-content: center;
    }

    .btn-send button {
        width: 30%;
        margin: 5px;
    }

    .form-group {
        display: flex;
        justify-content: center;
    }

    .form-control {
        padding: 8px;
    }

    form a {
        color: black;
        text-align: right;
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 20px;
        transition: transform 0.25s ease-in-out;
    }

    .modal.fade .modal-dialog {
        transform: translateY(-30px);
        transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
        transform: translateY(0);
    }

    .btn-custom {
        background-color: #8c99bc;
        border: none;
        transition: 0.3s;
    }

    .btn-custom:hover {
        background-color: #6f7ca1;
    }

    .modal-content {
        animation: fadeInUp 0.3s ease-in-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.97);
        border-radius: 20px;
        transition: transform 0.25s ease-in-out;
        animation: fadeInUp 0.3s ease-in-out;
    }

    .modal.fade .modal-dialog {
        transform: translateY(-30px);
        transition: transform 0.3s ease-out;
    }

    .modal.show .modal-dialog {
        transform: translateY(0);
    }

    .btn-custom,
    .btn-success,
    .btn-danger {
        background-color: #8c99bc;
        border: none;
        transition: 0.3s;
    }

    .btn-custom:hover,
    .btn-success:hover,
    .btn-danger:hover {
        background-color: #6f7ca1;
    }

    .text-muted {
        color: #333 !important;
    }

    #loadingOverlay {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #loadingOverlay .overlay-bg {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
    }

    #loadingOverlay .overlay-spinner {
        position: relative;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="container">
        <h4 style="font-weight: bold;">เข้าสู่ระบบ</h4>
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <form action="index.php" method="POST" autocomplete="off">
            <label for="username">ชื่อผู้ใช้งานหรืออีเมล</label>
            <input type="username" id="username" name="username" placeholder="กรอกชื่อผู้ใช้งานหรืออีเมล" required
                class="form-control"
                value="<?php echo htmlspecialchars($usernameOrEmail ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required
                class="form-control">
            <a href="#" id="forgotPasswordLink" data-toggle="modal" data-target="#forgotPasswordModal">ลืมรหัสผ่าน?</a>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>

        <a href="register.php">สมัครสมาชิก</a>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header justify-content-center border-0 pt-4">
                    <h5 class="modal-title font-weight-bold">ลืมรหัสผ่าน</h5>
                </div>
                <div class="modal-body text-center px-4 pb-4 pt-2">
                    <p class="text-muted mb-3">กรุณากรอกอีเมลที่คุณใช้สมัครสมาชิก</p>

                    <form id="forgotPasswordForm" method="POST" action="process_forgot_password.php">
                        <input type="email" id="forgotEmail" name="email" class="input-email form-control"
                            placeholder="กรุณาใส่อีเมลของคุณ" required autocomplete="off">

                        <div class="btn-send pt-2">
                            <button type="submit" id="sendLinkBtn"
                                class="btn btn-custom text-white px-4 py-2 font-weight-bold mr-2">ส่งลิงก์</button>

                            <button type="button" class="btn btn-outline-secondary px-4 py-2 font-weight-bold"
                                data-dismiss="modal">ยกเลิก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header justify-content-center border-0 pt-4">
                    <h5 class="modal-title font-weight-bold">
                        <?php echo ($modal_type === "success") ? "✅ สำเร็จ" : "⚠️ แจ้งเตือน"; ?>
                    </h5>
                </div>

                <div class="modal-body text-center px-4 pb-4 pt-2">
                    <p class="text-muted mb-3">
                        <?php echo htmlspecialchars($modal_message, ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <div class="btn-send pt-2">
                        <button type="button"
                            class="btn btn-<?php echo ($modal_type === "success") ? "success" : "danger"; ?> px-4 py-2 font-weight-bold"
                            data-dismiss="modal">
                            ตกลง
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="d-none">
        <div class="overlay-bg"></div>
        <div class="overlay-spinner">
            <div class="spinner-border text-light" role="status" style="width: 4rem; height: 4rem;">
            </div>
            <div class="text-light mt-3 fs-5">กำลังส่งอีเมล...</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {

        $('#forgotEmail').keypress(function(e) {
            if (e.which === 13) {
                $('#sendLinkBtn').click();
            }
        });

        <?php if (!empty($modal_message)): ?>
        $('#messageModal').modal('show');
        <?php endif; ?>
        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            const email = $('#forgotEmail').val().trim();
            if (email === '') {
                showMessageModal('danger', 'กรุณากรอกอีเมลของคุณ!');
                return;
            }

            $('#loadingOverlay').removeClass('d-none');

            $.ajax({
                url: 'process_forgot_password.php',
                type: 'POST',
                data: {
                    email: email
                },
                dataType: 'json',
                success: function(res) {
                    $('#forgotPasswordModal').modal('hide');
                    $('#forgotEmail').val('');
                    showMessageModal(res.status, res.message);
                },
                error: function() {
                    showMessageModal('danger', 'เกิดข้อผิดพลาดในการเชื่อมต่อ!');
                },
                complete: function() {
                    $('#loadingOverlay').addClass('d-none');
                }
            });
        });

        $('#forgotPasswordModal').on('hidden.bs.modal', function() {
            $('#forgotEmail').val('');
        });

        function showMessageModal(type, message) {
            const title = (type === 'success') ? '✅ สำเร็จ' : '⚠️ แจ้งเตือน';
            const btnClass = (type === 'success') ? 'success' : 'danger';
            $('#messageModal .modal-title').text(title);
            $('#messageModal .text-muted').text(message);
            $('#messageModal button').removeClass('btn-success btn-danger').addClass('btn-' + btnClass);
            $('#messageModal').modal('show');
        }
    });
    </script>
</body>

</html>