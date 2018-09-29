<?php
header("Access-Control-Allow-Origin: *");
if (isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["repassword"])) {
    $username = $_POST['username'];
    $pass = $_POST['password'];
    $repass = $_POST["repassword"];
    $lstAvatar = array('/img/avatar/1.jpg', '/img/avatar/2.jpg', '/img/avatar/3.jpg', '/img/avatar/4.jpg', '/img/avatar/5.jpg', '/img/avatar/6.jpg', '/img/avatar/7.jpg');
    if ($pass == $repass) {
        require_once '../Database/User.php';
        $user = new User();
        $check = $user->checkDangKi($username, $pass, $lstAvatar[rand(1, 7)]);
        if ($check == 2) {
            echo json_encode(array('status' => false, 'message' => 'Tên tài khoảng không hợp lệ'));
        } else if ($check == 1) {
            echo json_encode(array('status' => false, 'message' => 'Tài khoản đã tồn tại'));
        } else if ($check == 0) {
            echo json_encode(array('status' => true, 'message' => 'Đăng kí thành công'));
        } else if ($check == 3) {
            echo json_encode(array('status' => false, 'message' => 'Xảy ra lổi hệ thống'));
        }
    } else {
        echo json_encode(array('status' => false, 'message' => 'Mật khẩu không khớp'));
    }

}
