<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $userrole = $_POST["userrole"];

    $sql = "UPDATE users SET first_name=?, last_name=?, username=?, email=?, userrole=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $first_name, $last_name, $username, $email, $userrole, $user_id);

    if ($stmt->execute()) {
        header("Location: edit_user.php?user_id=$user_id&status=success");
    } else {
        header("Location: edit_user.php?user_id=$user_id&status=error");
    }
    exit();
}
?>
