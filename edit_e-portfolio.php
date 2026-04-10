<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งาน
$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_profile = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$first_name = $user_profile['first_name'] ?? 'ผู้ใช้งาน';
$full_name = ($user_profile['first_name'] ?? '') . ' ' . ($user_profile['last_name'] ?? '');

// จัดการรูปโปรไฟล์
$profile_image_file = (!empty($user_profile['profile_image']) && $user_profile['profile_image'] != 'default.png') ? $user_profile['profile_image'] : 'default.png';
$profile_image_url = 'uploads/profiles/' . $profile_image_file;
if (!file_exists($profile_image_url) && $profile_image_file !== 'default.png') {
     $profile_image_url = 'https://placehold.co/150x150?text=No+Image';
}

// ดึงข้อมูล Soft Skills
$sql_skills = "SELECT skill_name, skill_level FROM user_skills WHERE user_id = ?";
$stmt_skills = $conn->prepare($sql_skills);
$stmt_skills->bind_param("i", $user_id);
$stmt_skills->execute();
$result_skills = $stmt_skills->get_result();
$saved_skills = [];
while ($row = $result_skills->fetch_assoc()) {
    $saved_skills[$row['skill_name']] = $row['skill_level'];
}
$stmt_skills->close();

// ดึงข้อมูล Hard Skills
$hard_skills_data = [];
$sql_hs = "SELECT * FROM user_hard_skills WHERE user_id = ?";
$stmt_hs = $conn->prepare($sql_hs);
$stmt_hs->bind_param("i", $user_id);
$stmt_hs->execute();
$result_hs = $stmt_hs->get_result();
while ($row = $result_hs->fetch_assoc()) {
    $hard_skills_data[] = $row;
}
$stmt_hs->close();

// ดึงข้อมูลด้านภาษา (ใหม่)
$languages_data = [];
$sql_lang = "SELECT * FROM user_languages WHERE user_id = ?";
$stmt_lang = $conn->prepare($sql_lang);
$stmt_lang->bind_param("i", $user_id);
$stmt_lang->execute();
$result_lang = $stmt_lang->get_result();
while ($row = $result_lang->fetch_assoc()) {
    $languages_data[] = $row;
}
$stmt_lang->close();

// รายการ Soft Skills 15 ข้อ
$soft_skills = [
    'ss_1' => 'การสื่อสารที่ดี', 'ss_2' => 'การทำงานเป็นทีม', 'ss_3' => 'การแก้ปัญหาเฉพาะหน้า',
    'ss_4' => 'การคิดวิเคราะห์', 'ss_5' => 'การบริหารเวลา', 'ss_6' => 'ความรับผิดชอบต่อหน้าที่',
    'ss_7' => 'ความคิดสร้างสรรค์', 'ss_8' => 'การปรับตัวเข้ากับสถานการณ์', 'ss_9' => 'ภาวะผู้นำ',
    'ss_10' => 'การจัดการความเครียด', 'ss_11' => 'การมีมนุษยสัมพันธ์ที่ดี', 'ss_12' => 'ความละเอียดรอบคอบ',
    'ss_13' => 'ความมีวินัย', 'ss_14' => 'การรับฟังความคิดเห็นผู้อื่น', 'ss_15' => 'การตัดสินใจอย่างมีเหตุผล'
];

$level_options = ['ดีเยี่ยม', 'ดี', 'ปานกลาง', 'เริ่มต้น', 'ศึกษา'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล E-Portfolio - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --top-bar-bg: #A37E5E; --yellow-sidebar: #FEEFB3; --light-bg: #F4F6F9; --btn-blue: #6358E1; --text-dark: #333333; }
        body, html { height: 100%; margin: 0; font-family: 'Sarabun', sans-serif; background-color: var(--light-bg); overflow-x: hidden; }
        .top-navbar { background-color: var(--top-bar-bg); min-height: 80px; display: flex; align-items: center; padding: 10px 20px; justify-content: space-between; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); z-index: 100; position: sticky; top: 0; }
        .brand-section { display: flex; align-items: center; gap: 12px; }
        .brand-logo { width: 60px; height: 60px; }
        .brand-name { font-size: clamp(16px, 4vw, 24px); font-family: serif; letter-spacing: 1px; white-space: nowrap; }
        .text-page-pill-btn { background: white; color: black; padding: 3px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight: 500; }
        .login-pill-btn { background: white; color: black; padding: 6px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; transition: 0.3s; }
        .logout-area { text-align: center; margin-left: 20px; }
        .logout-text { color: #000; font-weight: bold; text-decoration: none; font-size: 14px; background: #D9D9D9; padding: 2px 10px; border-radius: 5px; display: block; }
        .form-section { background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); padding: 30px; margin-bottom: 30px; border: 1px solid #EBEBEB; }
        .section-title { color: var(--top-bar-bg); font-weight: 700; font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--yellow-sidebar); display: flex; align-items: center; gap: 10px; }
        .btn-pill { background: var(--btn-blue); color: white; border-radius: 50px; padding: 10px 30px; border: none; font-size: 16px; font-weight: bold; text-decoration: none; box-shadow: 0 4px 15px rgba(99, 88, 225, 0.3); transition: 0.3s; }
        .btn-pill:hover { transform: translateY(-2px); opacity: 0.9; color: white; box-shadow: 0 6px 20px rgba(99, 88, 225, 0.4); }
        .btn-outline-brown { background: transparent; border: 2px solid var(--top-bar-bg); color: var(--top-bar-bg); font-weight: bold; }
        .btn-outline-brown:hover { background: var(--top-bar-bg); color: white; }
        .form-label { font-weight: 500; color: var(--text-dark); }
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; border: 1px solid #DDD; }
        .form-control:focus, .form-select:focus { border-color: var(--top-bar-bg); box-shadow: 0 0 0 0.25rem rgba(163, 126, 94, 0.25); }
        .skill-table th { background-color: #F8F9FA; font-weight: 600; text-align: center; vertical-align: middle; }
        .skill-table td { vertical-align: middle; }
        .radio-cell { text-align: center; }
        .add-btn { background-color: var(--yellow-sidebar); color: var(--top-bar-bg); font-weight: bold; border: none; border-radius: 8px; padding: 8px 15px; }
        .add-btn:hover { background-color: #f7e28b; }
        @media (max-width: 768px) { .top-navbar { padding: 10px 15px; } .brand-name { font-size: 18px; } .logout-text { padding: 2px !important; font-size: 10px !important; } .form-section { padding: 20px; } }
    </style>
</head>

<body>
    <nav class="top-navbar">
        <div class="brand-section">
            <img src="img/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/60x60?text=Logo'">
            <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                <span class="brand-name">SMO SCITECH KPRU</span>
                <span class="text-page-pill-btn mt-1">E - portfolio</span>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <span class="d-none d-sm-block fw-bold me-2 login-pill-btn"><?php echo htmlspecialchars($first_name); ?></span>
            <div class="logout-area">
                <a href="user_management.php"><img src="<?php echo $profile_image_url; ?>" alt="Profile" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"></a>
                <a href="logout.php" class="logout-text mt-1">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <form action="update_portfolio.php" method="POST" enctype="multipart/form-data">

            <div class="form-section">
                <div class="section-title"><i class="fa-solid fa-user-pen"></i> ข้อมูลส่วนตัว</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">ชื่อ</label><input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user_profile['first_name'] ?? ''); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">นามสกุล</label><input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user_profile['last_name'] ?? ''); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">คณะ/สาขา</label><input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($user_profile['department'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label">อีเมล</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label">เบอร์โทรศัพท์</label><input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>"></div>
                    <div class="col-md-6"><label class="form-label">รูปโปรไฟล์ (อัปโหลดใหม่)</label><input type="file" class="form-control" name="profile_image" accept="image/*"></div>
                    <div class="col-12"><label class="form-label">แนะนำตัวเอง (About Me)</label><textarea class="form-control" name="about_me" rows="4" placeholder="เขียนแนะนำตัวสั้นๆ..."><?php echo htmlspecialchars($user_profile['about_me'] ?? ''); ?></textarea></div>
                </div>
            </div>

            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2" style="border-color: var(--yellow-sidebar) !important;">
                    <div class="section-title border-0 mb-0 pb-0"><i class="fa-solid fa-folder-plus"></i> ผลงาน / กิจกรรมเพิ่มเติม</div>
                    <button type="button" class="add-btn mb-2" id="addActivityBtn"><i class="fa-solid fa-plus"></i> เพิ่มผลงาน</button>
                </div>
                <div id="activityContainer">
                    <div class="row g-3 mb-4 p-3 border rounded bg-light activity-row">
                        <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-activity"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                        <div class="col-md-6"><label class="form-label">ชื่อผลงาน / กิจกรรม</label><input type="text" class="form-control" name="custom_act_title[]" placeholder="ระบุชื่อผลงาน"></div>
                        <div class="col-md-6"><label class="form-label">บทบาท / หน้าที่</label><input type="text" class="form-control" name="custom_act_role[]" placeholder="เช่น หัวหน้าทีม, ผู้เข้าร่วม"></div>
                        <div class="col-12"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="custom_act_desc[]" rows="2" placeholder="อธิบายสั้นๆ"></textarea></div>
                        <div class="col-12"><label class="form-label"><i class="fa-solid fa-image"></i> รูปภาพผลงาน (ถ้ามี)</label><input type="file" class="form-control" name="custom_act_image[]" accept="image/*"></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><i class="fa-solid fa-laptop-code"></i> ทักษะด้าน Hard Skills</div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-desktop"></i> ด้านคอมพิวเตอร์และเทคโนโลยีอื่นๆ</h6>
                    <button type="button" class="add-btn mb-2" id="addHardSkillBtn"><i class="fa-solid fa-plus"></i> เพิ่มทักษะ</button>
                </div>
                <div id="hardSkillContainer">
                    <?php if(count($hard_skills_data) > 0): ?>
                        <?php foreach($hard_skills_data as $hs): ?>
                        <div class="row g-3 mb-4 p-3 border rounded bg-light hard-skill-row">
                            <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-hard-skill"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                            <div class="col-md-6"><label class="form-label">ชื่อทักษะ (เช่น MS Office, Photoshop)</label><input type="text" class="form-control" name="hard_skill_name[]" value="<?php echo htmlspecialchars($hs['skill_name']); ?>" placeholder="ระบุทักษะ"></div>
                            <div class="col-md-6"><label class="form-label">ระดับทักษะ</label>
                                <select class="form-select" name="hard_skill_level[]">
                                    <?php foreach($level_options as $lvl): ?>
                                        <option value="<?php echo $lvl; ?>" <?php echo ($hs['skill_level']==$lvl)?'selected':''; ?>><?php echo $lvl; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row g-3 mb-4 p-3 border rounded bg-light hard-skill-row">
                            <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-hard-skill"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                            <div class="col-md-6"><label class="form-label">ชื่อทักษะ (เช่น MS Office, Photoshop)</label><input type="text" class="form-control" name="hard_skill_name[]" placeholder="ระบุทักษะ"></div>
                            <div class="col-md-6"><label class="form-label">ระดับทักษะ</label>
                                <select class="form-select" name="hard_skill_level[]">
                                    <option value="">-- เลือกระดับ --</option>
                                    <?php foreach($level_options as $lvl): echo "<option value=\"$lvl\">$lvl</option>"; endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top mb-3">
                    <h6 class="fw-bold mb-0"><i class="fa-solid fa-language"></i> ด้านภาษา</h6>
                    <button type="button" class="add-btn mb-2" id="addLanguageBtn"><i class="fa-solid fa-plus"></i> เพิ่มภาษา</button>
                </div>
                <div id="languageContainer">
                    <?php if(count($languages_data) > 0): ?>
                        <?php foreach($languages_data as $lang): ?>
                        <div class="row g-3 mb-4 p-3 border rounded language-row">
                            <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-language"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                            <div class="col-md-12"><label class="form-label fw-bold">ระบุภาษา (เช่น อังกฤษ, จีน, ญี่ปุ่น)</label>
                                <input type="text" class="form-control" name="lang_name[]" value="<?php echo htmlspecialchars($lang['lang_name']); ?>" placeholder="ระบุภาษา">
                            </div>
                            <div class="col-md-3"><label class="form-label small">การฟัง</label>
                                <select class="form-select form-select-sm" name="lang_listen[]">
                                    <?php foreach($level_options as $lvl): ?><option value="<?php echo $lvl; ?>" <?php echo ($lang['lang_listen']==$lvl)?'selected':''; ?>><?php echo $lvl; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">การพูด</label>
                                <select class="form-select form-select-sm" name="lang_speak[]">
                                    <?php foreach($level_options as $lvl): ?><option value="<?php echo $lvl; ?>" <?php echo ($lang['lang_speak']==$lvl)?'selected':''; ?>><?php echo $lvl; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">การอ่าน</label>
                                <select class="form-select form-select-sm" name="lang_read[]">
                                    <?php foreach($level_options as $lvl): ?><option value="<?php echo $lvl; ?>" <?php echo ($lang['lang_read']==$lvl)?'selected':''; ?>><?php echo $lvl; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label small">การเขียน</label>
                                <select class="form-select form-select-sm" name="lang_write[]">
                                    <?php foreach($level_options as $lvl): ?><option value="<?php echo $lvl; ?>" <?php echo ($lang['lang_write']==$lvl)?'selected':''; ?>><?php echo $lvl; ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row g-3 mb-4 p-3 border rounded language-row">
                            <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-language"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                            <div class="col-md-12"><label class="form-label fw-bold">ระบุภาษา (เช่น อังกฤษ, จีน, ญี่ปุ่น)</label>
                                <input type="text" class="form-control" name="lang_name[]" placeholder="ระบุภาษา">
                            </div>
                            <div class="col-md-3"><label class="form-label small">การฟัง</label><select class="form-select form-select-sm" name="lang_listen[]"><option value="">-- เลือกระดับ --</option><?php foreach($level_options as $lvl) echo "<option value='$lvl'>$lvl</option>"; ?></select></div>
                            <div class="col-md-3"><label class="form-label small">การพูด</label><select class="form-select form-select-sm" name="lang_speak[]"><option value="">-- เลือกระดับ --</option><?php foreach($level_options as $lvl) echo "<option value='$lvl'>$lvl</option>"; ?></select></div>
                            <div class="col-md-3"><label class="form-label small">การอ่าน</label><select class="form-select form-select-sm" name="lang_read[]"><option value="">-- เลือกระดับ --</option><?php foreach($level_options as $lvl) echo "<option value='$lvl'>$lvl</option>"; ?></select></div>
                            <div class="col-md-3"><label class="form-label small">การเขียน</label><select class="form-select form-select-sm" name="lang_write[]"><option value="">-- เลือกระดับ --</option><?php foreach($level_options as $lvl) echo "<option value='$lvl'>$lvl</option>"; ?></select></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title"><i class="fa-solid fa-users-gear"></i> การประเมินทักษะ Soft Skills (15 ข้อ)</div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered skill-table align-middle">
                        <thead style="background-color: var(--top-bar-bg); color: white;">
                            <tr>
                                <th style="width: 5%; background-color: transparent; color: white;">#</th>
                                <th style="width: 45%; text-align: left; background-color: transparent; color: white;">ทักษะ / ความสามารถ</th>
                                <th style="background-color: transparent; color: white;">ดีเยี่ยม (5)</th>
                                <th style="background-color: transparent; color: white;">ดี (4)</th>
                                <th style="background-color: transparent; color: white;">ปานกลาง (3)</th>
                                <th style="background-color: transparent; color: white;">พอใช้ (2)</th>
                                <th style="background-color: transparent; color: white;">ปรับปรุง (1)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            foreach ($soft_skills as $key => $skill_name): 
                                $score = isset($saved_skills[$skill_name]) ? $saved_skills[$skill_name] : 0;
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $i; ?></td>
                                <td class="fw-medium"><?php echo $skill_name; ?></td>
                                <td class="radio-cell"><input class="form-check-input" type="radio" name="<?php echo $key; ?>" value="5" <?php echo ($score == 5)?'checked':''; ?>></td>
                                <td class="radio-cell"><input class="form-check-input" type="radio" name="<?php echo $key; ?>" value="4" <?php echo ($score == 4)?'checked':''; ?>></td>
                                <td class="radio-cell"><input class="form-check-input" type="radio" name="<?php echo $key; ?>" value="3" <?php echo ($score == 3)?'checked':''; ?>></td>
                                <td class="radio-cell"><input class="form-check-input" type="radio" name="<?php echo $key; ?>" value="2" <?php echo ($score == 2)?'checked':''; ?>></td>
                                <td class="radio-cell"><input class="form-check-input" type="radio" name="<?php echo $key; ?>" value="1" <?php echo ($score == 1)?'checked':''; ?>></td>
                            </tr>
                            <?php $i++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4 mb-5">
                <button type="submit" class="btn-pill px-5">บันทึกข้อมูล</button>
                <a href="e-portfolio.php" class="btn btn-pill btn-outline-brown px-5" style="background: white; border: 2px solid var(--top-bar-bg); color: var(--top-bar-bg);">กลับ</a>
            </div>

        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        const levelOptionsHTML = `
            <option value="">-- เลือกระดับ --</option>
            <option value="ดีเยี่ยม">ดีเยี่ยม</option>
            <option value="ดี">ดี</option>
            <option value="ปานกลาง">ปานกลาง</option>
            <option value="เริ่มต้น">เริ่มต้น</option>
            <option value="ศึกษา">ศึกษา</option>
        `;

        // เพิ่มฟอร์มผลงาน
        $('#addActivityBtn').click(function() {
            $('#activityContainer').append(`
                <div class="row g-3 mb-4 p-3 border rounded bg-light activity-row">
                    <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-activity"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                    <div class="col-md-6"><label class="form-label">ชื่อผลงาน / กิจกรรม</label><input type="text" class="form-control" name="custom_act_title[]" placeholder="ระบุชื่อผลงาน"></div>
                    <div class="col-md-6"><label class="form-label">บทบาท / หน้าที่</label><input type="text" class="form-control" name="custom_act_role[]" placeholder="เช่น หัวหน้าทีม, ผู้เข้าร่วม"></div>
                    <div class="col-12"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="custom_act_desc[]" rows="2" placeholder="อธิบายสั้นๆ"></textarea></div>
                    <div class="col-12"><label class="form-label"><i class="fa-solid fa-image"></i> รูปภาพผลงาน (ถ้ามี)</label><input type="file" class="form-control" name="custom_act_image[]" accept="image/*"></div>
                </div>
            `);
        });

        // เพิ่มฟอร์ม Hard Skill (คอมพิวเตอร์)
        $('#addHardSkillBtn').click(function() {
            $('#hardSkillContainer').append(`
                <div class="row g-3 mb-4 p-3 border rounded bg-light hard-skill-row">
                    <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-hard-skill"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                    <div class="col-md-6"><label class="form-label">ชื่อทักษะ</label><input type="text" class="form-control" name="hard_skill_name[]" placeholder="ระบุทักษะ"></div>
                    <div class="col-md-6"><label class="form-label">ระดับทักษะ</label><select class="form-select" name="hard_skill_level[]">${levelOptionsHTML}</select></div>
                </div>
            `);
        });

        // เพิ่มฟอร์มภาษา
        $('#addLanguageBtn').click(function() {
            $('#languageContainer').append(`
                <div class="row g-3 mb-4 p-3 border rounded language-row">
                    <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-danger remove-language"><i class="fa-solid fa-trash"></i> ลบ</button></div>
                    <div class="col-md-12"><label class="form-label fw-bold">ระบุภาษา (เช่น อังกฤษ, จีน)</label><input type="text" class="form-control" name="lang_name[]" placeholder="ระบุภาษา"></div>
                    <div class="col-md-3"><label class="form-label small">การฟัง</label><select class="form-select form-select-sm" name="lang_listen[]">${levelOptionsHTML}</select></div>
                    <div class="col-md-3"><label class="form-label small">การพูด</label><select class="form-select form-select-sm" name="lang_speak[]">${levelOptionsHTML}</select></div>
                    <div class="col-md-3"><label class="form-label small">การอ่าน</label><select class="form-select form-select-sm" name="lang_read[]">${levelOptionsHTML}</select></div>
                    <div class="col-md-3"><label class="form-label small">การเขียน</label><select class="form-select form-select-sm" name="lang_write[]">${levelOptionsHTML}</select></div>
                </div>
            `);
        });

        // ลบฟอร์ม (ใช้ Class ในการลบ)
        $(document).on('click', '.remove-activity', function() { $(this).closest('.activity-row').remove(); });
        $(document).on('click', '.remove-hard-skill', function() { $(this).closest('.hard-skill-row').remove(); });
        $(document).on('click', '.remove-language', function() { $(this).closest('.language-row').remove(); });
    });
    </script>
</body>
</html>