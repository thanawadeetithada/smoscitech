<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$registration_id = isset($_GET['reg_id']) ? intval($_GET['reg_id']) : 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    
    
    if (isset($_POST['images_to_delete']) && is_array($_POST['images_to_delete'])) {
        foreach ($_POST['images_to_delete'] as $del_id) {
            $del_id = intval($del_id);
            
            
            $stmt_del_sel = $conn->prepare("SELECT image_path FROM activity_evidences WHERE evidence_id = ? AND registration_id = ?");
            $stmt_del_sel->bind_param("ii", $del_id, $registration_id);
            $stmt_del_sel->execute();
            $res_del = $stmt_del_sel->get_result();
            
            if ($row_del = $res_del->fetch_assoc()) {
                $file_to_delete = 'uploads/evidences/' . $row_del['image_path'];
                
                if ($row_del['image_path'] != '' && file_exists($file_to_delete) && is_file($file_to_delete)) {
                    unlink($file_to_delete); 
                }
                
                
                $stmt_del = $conn->prepare("DELETE FROM activity_evidences WHERE evidence_id = ?");
                $stmt_del->bind_param("i", $del_id);
                $stmt_del->execute();
            }
        }
    }

    
    
    
    $description = $_POST['description'] ?? '';
    
    
    $update_desc = $conn->prepare("UPDATE activity_evidences SET description = ? WHERE registration_id = ?");
    $update_desc->bind_param("si", $description, $registration_id);
    $update_desc->execute();

    
    if (!empty($_FILES['evidence_files']['name'][0])) {
        $upload_dir = 'uploads/evidences/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); 
        }

        $file_count = count($_FILES['evidence_files']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            $tmp_name = $_FILES['evidence_files']['tmp_name'][$i];
            $file_name = $_FILES['evidence_files']['name'][$i];
            $file_error = $_FILES['evidence_files']['error'][$i];

            if ($file_error === UPLOAD_ERR_OK) {
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_ev_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    $insert_ev = $conn->prepare("INSERT INTO activity_evidences (registration_id, image_path, description) VALUES (?, ?, ?)");
                    $insert_ev->bind_param("iss", $registration_id, $new_filename, $description);
                    $insert_ev->execute();
                }
            }
        }
    } else {
        
        
        $check_ev = $conn->prepare("SELECT evidence_id FROM activity_evidences WHERE registration_id = ?");
        $check_ev->bind_param("i", $registration_id);
        $check_ev->execute();
        $ev_result = $check_ev->get_result();
        
        if($ev_result->num_rows == 0) {
            $insert_ev = $conn->prepare("INSERT INTO activity_evidences (registration_id, image_path, description) VALUES (?, '', ?)");
            $insert_ev->bind_param("is", $registration_id, $description);
            $insert_ev->execute();
        }
    }
    
    $_SESSION['status_modal'] = ['type' => 'success', 'title' => 'สำเร็จ', 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];
    header("Location: activity.php");
    exit();
}


$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';


$stmt = $conn->prepare("
    SELECT a.title, e.description 
    FROM activity_registrations ar
    JOIN activities a ON ar.activity_id = a.activity_id
    LEFT JOIN activity_evidences e ON ar.registration_id = e.registration_id
    WHERE ar.registration_id = ? AND ar.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $registration_id, $user_id);
$stmt->execute();
$activity_data = $stmt->get_result()->fetch_assoc();

if (!$activity_data) {
    echo "<script>alert('ไม่พบข้อมูลกิจกรรม'); window.location='activity.php';</script>";
    exit();
}


$stmt_imgs = $conn->prepare("SELECT evidence_id, image_path FROM activity_evidences WHERE registration_id = ? AND image_path != ''");
$stmt_imgs->bind_param("i", $registration_id);
$stmt_imgs->execute();
$existing_images = $stmt_imgs->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดหลักฐาน - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9;
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
        font-weight: bold;
        font-size: 16px;
    }

    .text-page-pill-btn {
        background: white;
        color: black;
        padding: 3px 15px;
        border-radius: 5px;
        font-size: 13px;
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
        transition: all 0.3s;
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
        flex-direction: column;
        background-color: white;
    }

    .upload-container-box {
        width: 100%;
        margin: 0 auto;
        min-height: 550px;
        display: flex;
        flex-direction: column;
    }

    .box-header {
        padding: 10px;
        font-size: 16px;
        font-weight: bold;
        color: #333;
        background-color: #FEFBEA;
    }

    .inner-grey-box {
        background-color: #EBECF0;
        padding: 30px;
        margin-top: 20px;
        border-radius: 5px;
        flex-grow: 1;
    }

    .inner-title {
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
        font-size: 16px;
    }

    .form-group-row {
        display: flex;
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: bold;
        color: #333;
        flex-shrink: 0;
        margin-right: 15px;
        width: 180px;
    }

    .custom-textarea {
        flex-grow: 1;
        border-radius: 10px;
        border: none;
        padding: 15px;
        height: 120px;
        resize: none;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        width: 100%;
    }

    .upload-boxes-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        flex-grow: 1;
        width: 100%;
    }

    
    .file-upload-input {
        display: none;
    }

    .btn-upload-label {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 25px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        display: inline-block;
        transition: 0.2s;
    }

    .btn-upload-label:hover {
        background-color: #4a40bd;
    }

    .preview-area {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }

    .preview-img-box {
        position: relative;
        width: 120px;
        height: 120px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .preview-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    
    .btn-delete-img {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .btn-delete-img:hover {
        background-color: #c82333;
        transform: scale(1.1);
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        justify-content: flex-start;
    }

    .btn-action-bottom {
        background-color: #6358E1;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 30px;
        font-size: 14px;
        text-decoration: none;
        cursor: pointer;
        text-align: center;
    }

    .btn-action-bottom:hover {
        background-color: #4a40bd;
        color: white;
    }

    @media (max-width: 768px) {
        .top-navbar {
            padding: 10px 15px;
        }

        .brand-name {
            font-size: 18px;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 10px !important;
        }

        .logout-area {
            margin-left: 10px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            height: 100%;
            width: 250px;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .sidebar.active {
            left: 0;
        }

        .content-area {
            padding: 15px;
        }

        .inner-grey-box {
            padding: 15px;
            margin: 10px 0;
        }

        .form-group-row {
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 10px;
            width: 100%;
        }

        .action-buttons {
            justify-content: center;
            gap: 10px;
        }

        .btn-action-bottom {
            width: fit-content;
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
                <img src="img/logo.png" alt="Logo" class="brand-logo"
                    onerror="this.src='https://via.placeholder.com/60'">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">ข้อมูลกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($first_name); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white;"
                            onerror="this.src='https://via.placeholder.com/45'">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">ออกจากระบบ</a>
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
                    <span>ข้อมูลสมาชิกสโมสร</span>
                </a>
                <a href="activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>
            </aside>

            <main class="content-area">
                <div class="upload-container-box">
                    <div class="box-header">รายละเอียดกิจกรรมที่เคยลงทะเบียน</div>

                    <div class="inner-grey-box">
                        <div class="inner-title">
                            ชื่อกิจกรรม : <?php echo htmlspecialchars($activity_data['title']); ?>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data" class="d-flex flex-column" style="flex-grow:1;" id="mainUploadForm">
                            <div class="form-group-row">
                                <div class="form-label">รายละเอียดการทำงาน :</div>
                                <textarea name="description"
                                    class="custom-textarea"><?php echo htmlspecialchars($activity_data['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group-row">
                                <div class="form-label">หลักฐานการทำกิจกรรม :</div>
                                <div class="upload-boxes-container">
                                    
                                    <div>
                                        <label for="evidence_files" class="btn-upload-label">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> เลือกไฟล์รูปภาพใหม่ (หลายไฟล์ได้)
                                        </label>
                                        <input type="file" name="evidence_files[]" id="evidence_files" class="file-upload-input" multiple accept="image/*">
                                    </div>

                                    <div id="new_preview_area" class="preview-area" style="display: none;"></div>

                                    <?php if($existing_images->num_rows > 0): ?>
                                    <div class="mt-3" id="existing_images_section">
                                        <strong style="font-size: 14px;">รูปภาพที่อัปโหลดไว้แล้ว:</strong>
                                        <div class="preview-area mt-2">
                                            <?php while($img = $existing_images->fetch_assoc()): ?>
                                                <div class="preview-img-box" id="img_box_<?php echo $img['evidence_id']; ?>">
                                                    <img src="uploads/evidences/<?php echo htmlspecialchars($img['image_path']); ?>" alt="Evidence Image">
                                                    
                                                    <button type="button" class="btn-delete-img" title="ลบรูปภาพ" onclick="markForDeletion(this, <?php echo $img['evidence_id']; ?>)">
                                                        <i class="fa-solid fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                            
                            <div class="action-buttons pb-4">
                                <a href="activity.php" class="btn-action-bottom">กลับ</a>
                                <button type="submit" class="btn-action-bottom">Upload</button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
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

        
        const fileInput = document.getElementById('evidence_files');
        const previewArea = document.getElementById('new_preview_area');

        fileInput.addEventListener('change', function(event) {
            previewArea.innerHTML = ''; 
            const files = event.target.files;

            if (files.length > 0) {
                previewArea.style.display = 'flex'; 
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgBox = document.createElement('div');
                            imgBox.className = 'preview-img-box';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            
                            imgBox.appendChild(img);
                            previewArea.appendChild(imgBox);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            } else {
                previewArea.style.display = 'none'; 
            }
        });
    });

    function markForDeletion(btnElement, evidenceId) {
        
        const imgBox = btnElement.closest('.preview-img-box');
        imgBox.style.display = 'none';

        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'images_to_delete[]';
        hiddenInput.value = evidenceId;
        
        
        document.getElementById('mainUploadForm').appendChild(hiddenInput);
    }
    </script>
</body>

</html>