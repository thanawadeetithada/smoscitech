<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $about_me = trim($_POST['about_me'] ?? '');

    $sql = "UPDATE users SET 
            first_name=?, last_name=?, department=?, email=?, phone=?, about_me=?
            WHERE user_id=?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", 
        $first_name, $last_name, $department, $email, $phone, $about_me,
        $user_id
    );
    $stmt->execute();
    $stmt->close();

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $sql_img = "UPDATE users SET profile_image=? WHERE user_id=?";
            $stmt_img = $conn->prepare($sql_img);
            $stmt_img->bind_param("si", $new_filename, $user_id);
            $stmt_img->execute();
            $stmt_img->close();
        }
    }

    $conn->query("DELETE FROM user_skills WHERE user_id = $user_id");

    $soft_skills_list = [
        'ss_1' => 'การสื่อสารที่ดี', 'ss_2' => 'การทำงานเป็นทีม', 'ss_3' => 'การแก้ปัญหาเฉพาะหน้า',
        'ss_4' => 'การคิดวิเคราะห์', 'ss_5' => 'การบริหารเวลา', 'ss_6' => 'ความรับผิดชอบต่อหน้าที่',
        'ss_7' => 'ความคิดสร้างสรรค์', 'ss_8' => 'การปรับตัวเข้ากับสถานการณ์', 'ss_9' => 'ภาวะผู้นำ',
        'ss_10' => 'การจัดการความเครียด', 'ss_11' => 'การมีมนุษยสัมพันธ์ที่ดี', 'ss_12' => 'ความละเอียดรอบคอบ',
        'ss_13' => 'ความมีวินัย', 'ss_14' => 'การรับฟังความคิดเห็นผู้อื่น', 'ss_15' => 'การตัดสินใจอย่างมีเหตุผล'
    ];

    $sql_insert_skill = "INSERT INTO user_skills (user_id, skill_name, skill_level) VALUES (?, ?, ?)";
    $stmt_skill = $conn->prepare($sql_insert_skill);

    foreach ($soft_skills_list as $key => $skill_name) {
        if (isset($_POST[$key]) && $_POST[$key] > 0) {
            $skill_level = (int)$_POST[$key];
            $stmt_skill->bind_param("isi", $user_id, $skill_name, $skill_level);
            $stmt_skill->execute();
        }
    }
    $stmt_skill->close();

    $conn->query("DELETE FROM user_hard_skills WHERE user_id = $user_id");

    if (isset($_POST['hard_skill_name']) && is_array($_POST['hard_skill_name'])) {
        $sql_hs = "INSERT INTO user_hard_skills (user_id, skill_name, skill_level) VALUES (?, ?, ?)";
        $stmt_hs = $conn->prepare($sql_hs);

        for ($i = 0; $i < count($_POST['hard_skill_name']); $i++) {
            $hs_name = trim($_POST['hard_skill_name'][$i]);
            $hs_level = trim($_POST['hard_skill_level'][$i]);

            if (!empty($hs_name)) {
                $stmt_hs->bind_param("iss", $user_id, $hs_name, $hs_level);
                $stmt_hs->execute();
            }
        }
        $stmt_hs->close();
    }

    $custom_act_ids = $_POST['custom_act_id'] ?? [];
    $existing_images = $_POST['existing_custom_act_image'] ?? [];
    
    $kept_ids = array_filter($custom_act_ids);
    if (!empty($kept_ids)) {
        $ids_str = implode(',', array_map('intval', $kept_ids));
        $conn->query("DELETE FROM user_custom_activities WHERE user_id = $user_id AND id NOT IN ($ids_str)");
    } else {
        $conn->query("DELETE FROM user_custom_activities WHERE user_id = $user_id");
    }

    if (isset($_POST['custom_act_title']) && is_array($_POST['custom_act_title'])) {
        for ($i = 0; $i < count($_POST['custom_act_title']); $i++) {
            $act_id = trim($custom_act_ids[$i] ?? '');
            $act_title = trim($_POST['custom_act_title'][$i]);
            $act_role = trim($_POST['custom_act_role'][$i] ?? '');
            $act_desc = trim($_POST['custom_act_desc'][$i] ?? '');
            
            $act_image_name = trim($existing_images[$i] ?? '');
            if (empty($act_image_name)) {
                $act_image_name = NULL;
            }

            if (isset($_FILES['custom_act_image']['name'][$i]) && $_FILES['custom_act_image']['error'][$i] == 0) {
                $act_target_dir = "uploads/activities/";
                if (!is_dir($act_target_dir)) mkdir($act_target_dir, 0777, true);

                $ext = pathinfo($_FILES['custom_act_image']['name'][$i], PATHINFO_EXTENSION);
                $new_image_name = "act_" . $user_id . "_" . time() . "_" . $i . "." . $ext;
                $act_target_file = $act_target_dir . $new_image_name;

                if (move_uploaded_file($_FILES['custom_act_image']['tmp_name'][$i], $act_target_file)) {
                    $act_image_name = $new_image_name;
                }
            }

            if (!empty($act_title)) {
                if (!empty($act_id)) {
                    $sql_update = "UPDATE user_custom_activities SET title=?, role=?, description=?, image_path=? WHERE id=? AND user_id=?";
                    $stmt_up = $conn->prepare($sql_update);
                    $stmt_up->bind_param("ssssii", $act_title, $act_role, $act_desc, $act_image_name, $act_id, $user_id);
                    $stmt_up->execute();
                    $stmt_up->close();
                } else {
                    $sql_insert = "INSERT INTO user_custom_activities (user_id, title, role, description, image_path) VALUES (?, ?, ?, ?, ?)";
                    $stmt_in = $conn->prepare($sql_insert);
                    $stmt_in->bind_param("issss", $user_id, $act_title, $act_role, $act_desc, $act_image_name);
                    $stmt_in->execute();
                    $stmt_in->close();
                }
            }
        }
    }

    $conn->query("DELETE FROM user_languages WHERE user_id = $user_id");

    if (isset($_POST['lang_name']) && is_array($_POST['lang_name'])) {
        $sql_lang = "INSERT INTO user_languages (user_id, lang_name, lang_listen, lang_speak, lang_read, lang_write) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_l = $conn->prepare($sql_lang);

        for ($i = 0; $i < count($_POST['lang_name']); $i++) {
            $l_name   = trim($_POST['lang_name'][$i]);
            $l_listen = trim($_POST['lang_listen'][$i] ?? '');
            $l_speak  = trim($_POST['lang_speak'][$i] ?? '');
            $l_read   = trim($_POST['lang_read'][$i] ?? '');
            $l_write  = trim($_POST['lang_write'][$i] ?? '');

            if (!empty($l_name)) {
                $stmt_l->bind_param("isssss", $user_id, $l_name, $l_listen, $l_speak, $l_read, $l_write);
                $stmt_l->execute();
            }
        }
        $stmt_l->close();
    }

    header("Location: e-portfolio.php?update=success");
    exit();
} else {
    header("Location: e-portfolio.php");
    exit();
}
?>