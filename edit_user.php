<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['user_id'])) {
    die("ไม่มีการระบุ ID");
}

$user_id = $_GET['user_id'];

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("ไม่พบข้อมูล");
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>แก้ไขข้อมูลผู้ใช้งาน</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        height: auto;
        background: url('bg/sky.png') no-repeat center center/cover;
        margin: 0;
        background: #cfd8e5;
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

    .container {
        background: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
        margin: 20px;
    }

    h2 {
        margin-bottom: 20px;
        color: black;
        text-align: center;
        margin-top: 20px;
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
    }


    button {
        width: 48%;
        padding: 12px;
        font-size: 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .submit-btn {
        background: #8c99bc;
        color: white;
    }

    .cancel-btn {
        background: #ccc;
        color: black;
        margin-left: 5px;
    }

    .form-control {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
    }

    .form-label {
        margin-top: 10px;
        margin-bottom: 0;
    }

    .container-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 56px);
    }

    .bg-purple {
        background-color: #8c99bc !important;
    }

    .btn-group-responsive {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        flex-wrap: nowrap;
    }

    .btn-group-responsive .btn {
        flex: 1 1 200px;
        max-width: 200px;
    }

    @media (max-width: 576px) {
        .btn-group-responsive .btn {
            width: 100%;
            max-width: none;
        }
    }

    .btn-purple {
        width: 20%;
        background-color: #8c99bc !important;
        color: white !important;
        border: none;
    }

    .btn-purple:hover {
        background-color: #9FA8DA !important;
    }

    .btn-cancel {
        width: 20%;
        background-color: #c7c5c5 !important;
        color: black !important;
    }

    .btn-cancel:hover {
        background-color: #E8E8E8 !important;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
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
    <div class="container-wrapper">
        <div class="container">
            <h2>แก้ไขข้อมูลผู้ใช้งาน</h2>
            <form action="update_user.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="first_name" class="form-label">ชื่อ</label>
                        <input class="form-control" type="text" id="first_name" name="first_name"
                            value="<?= $row['first_name'] ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="last_name" class="form-label">นามสกุล</label>
                        <input class="form-control" type="text" id="last_name" name="last_name"
                            value="<?= $row['last_name'] ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                        <input class="form-control" type="text" id="username" name="username"
                            value="<?= $row['username'] ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="email" class="form-label">E-mail</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= $row['email'] ?>"
                            required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="academic_year" class="form-label">ปีการศึกษา</label>
                        <input class="form-control" type="text" id="academic_year" name="academic_year"
                            value="<?= $row['academic_year'] ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="department" class="form-label">สาขาวิชา</label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="วิทยาการคอมพิวเตอร์"
                                <?= ($row['department'] == 'วิทยาการคอมพิวเตอร์') ? 'selected' : ''; ?>>
                                วิทยาการคอมพิวเตอร์</option>
                            <option value="เทคโนโลยีสารสนเทศ"
                                <?= ($row['department'] == 'เทคโนโลยีสารสนเทศ') ? 'selected' : ''; ?>>เทคโนโลยีสารสนเทศ
                            </option>
                            <option value="นวัตกรรมและธุรกิจอาหาร"
                                <?= ($row['department'] == 'นวัตกรรมและธุรกิจอาหาร') ? 'selected' : ''; ?>>
                                นวัตกรรมและธุรกิจอาหาร</option>
                            <option value="สาธารณสุขศาสตร์"
                                <?= ($row['department'] == 'สาธารณสุขศาสตร์') ? 'selected' : ''; ?>>สาธารณสุขศาสตร์
                            </option>
                            <option value="เคมี (วท.บ.)"
                                <?= ($row['department'] == 'เคมี (วท.บ.)') ? 'selected' : ''; ?>>เคมี (วท.บ.)
                            </option>
                            <option value="วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม"
                                <?= ($row['department'] == 'วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม') ? 'selected' : ''; ?>>
                                วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม</option>
                            <option value="ฟิสิกส์" <?= ($row['department'] == 'ฟิสิกส์') ? 'selected' : ''; ?>>
                                ฟิสิกส์</option>
                            <option value="เคมี (ค.บ.)" <?= ($row['department'] == 'เคมี (ค.บ.)') ? 'selected' : ''; ?>>
                                เคมี (ค.บ.)</option>
                            <option value="ชีววิทยา" <?= ($row['department'] == 'ชีววิทยา') ? 'selected' : ''; ?>>
                                ชีววิทยา</option>
                            <option value="คณิตศาสตร์ประยุกต์"
                                <?= ($row['department'] == 'คณิตศาสตร์ประยุกต์') ? 'selected' : ''; ?>>
                                คณิตศาสตร์ประยุกต์</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="userrole" class="form-label">สถานะ</label>
                        <select class="form-control" id="userrole" name="userrole" required>
                            <option value="">-- เลือกประเภท --</option>
                            <option value="executive" <?= ($row['userrole'] == 'executive') ? 'selected' : ''; ?>>
                                ผู้บริหาร</option>
                            <option value="academic_officer"
                                <?= ($row['userrole'] == 'academic_officer') ? 'selected' : ''; ?>>นักวิชาการศึกษา
                            </option>
                            <option value="club_president"
                                <?= ($row['userrole'] == 'club_president') ? 'selected' : ''; ?>>
                                นายกสโมสรนักศึกษาและรองนายกสโมสรนักศึกษา</option>
                            <option value="club_member" <?= ($row['userrole'] == 'club_member') ? 'selected' : ''; ?>>
                                สมาชิกสโมสรนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยี</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="profile_image" class="form-label">รูปโปรไฟล์</label>
                        <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*"
                            onchange="previewImage(event)">
                            <br>
                        <div class="mb-2 text-center">
                            <?php 
                    $image_src = "bg/default-profile.png";
                    if (!empty($row['profile_image']) && file_exists("uploads/" . $row['profile_image'])) {
                        $image_src = "uploads/" . $row['profile_image'];
                    }
                ?>
                            <img id="preview" src="<?= $image_src ?>" alt="Preview"
                                style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; display: inline-block;">
                        </div>
                    </div>
                </div>
                <br>
                <div class="d-flex text-center btn-group-responsive mt-4">
                    <button type="submit" class="btn btn-purple">บันทึก</button>
                    <button type="button" onclick="window.location.href='admin_user_management.php'"
                        class="btn btn-cancel">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title" id="statusModalLabel">อัปเดต</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="statusMessage">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-purple" id="closeModalBtn" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'success') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-2x mb-2'></i><br>อัปเดตข้อมูลเรียบร้อยแล้ว"
            );
            $("#statusModal").modal("show");
        } else if (status === 'error') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-triangle-exclamation text-danger fa-2x mb-2'></i><br>เกิดข้อผิดพลาดในการอัปเดตข้อมูล"
            );
            $("#statusModal").modal("show");
        }

        $("#closeModalBtn").on("click", function() {
            window.location.href = "admin_user_management.php";
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

        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }
    </script>
</body>

</html>