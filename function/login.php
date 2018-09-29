<?php
header("Access-Control-Allow-Origin: *");
if ($_POST["username"] || $_POST["password"]) {
    $username = $_POST['username'];
    $pass = $_POST['password'];

    require_once('../Database/User.php');
    $user = new User();
    if ($user->checkLogin($username, $pass) == true) {
        $info = $user->getUserByID($username);
        echo json_encode(array('status' => true, 'info' => $info));
    } else {
        echo json_encode(array('status' => false, 'info' => null));
    }
}