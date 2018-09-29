<?php
include_once 'ConnectDatabase.php';
class User extends ConnectDatabase
{
    private $db;
    public function User()
    {
        $this->db = new ConnectDatabase();
    }

    public function getAllUser()
    {
        $this->db->connect_db();
        $sql = 'SELECT * FROM tb_user';
        $query = mysqli_query($this->db->conn, $sql);
        $result = array();
        if ($query != null) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
        }

        $res = json_encode($result);
        return $res;
    }

    public function getUserByID($username)
    {
        $this->db->connect_db();
        $sql = 'SELECT * FROM tb_user WHERE user_name = \'' . $username . '\'';
        $query = mysqli_query($this->db->conn, $sql);
        $result = array();
        if ($query != null) {
            while ($row = mysqli_fetch_assoc($query)) {
                $result[] = $row;
            }
        }

        $res = json_encode($result);
        return $res;
    }

    public function checkLogin($username, $password)
    {
        $this->db->connect_db();
        $sql = 'SELECT * FROM tb_user WHERE user_name = \'' . $username . '\' and password = \'' . md5($password) . '\'';
        $query = mysqli_query($this->db->conn, $sql);
        $result = mysqli_num_rows($query);

        if ($result == 0) {
            return 'false';
        } else {
            return 'true';
        }

    }

    public function updateCredit($username, $credit, $isIncrease)
    {
        $sql = null;
        $result = 0;
        if ($isIncrease == 1) {
            // + thêm
            $sql = 'UPDATE tb_user SET user_credit = user_credit + ' . $credit . ' WHERE user_name = \'' . $username . '\'';
        } else {
            // - đi
            $sql = 'UPDATE tb_user SET user_credit = user_credit - ' . $credit . ' WHERE user_name = \'' . $username . '\'';
        }
        $this->db->connect_db();
        $query = mysqli_query($this->db->conn, $sql);
        return $query;
    }

    public function getLstAvatar($lstUsername, $chuPhong)
    {
        // echo json_encode($lstUsername)."\n";
        $lstAvatar = array();
        foreach ($lstUsername as $key => $item) {
            $sql = 'SELECT avatar,user_credit FROM tb_user WHERE user_name = \'' . $item['name'] . '\'';
            $this->db->connect_db();
            $query = mysqli_query($this->db->conn, $sql);
            if ($query != null) {
                while ($row = mysqli_fetch_assoc($query)) {
                    $new = array(
                        'avatar' => $row['avatar'],
                        'name' => $item['name'],
                        'credit' => (float) $row['user_credit'],
                        'color' => $item['color'],
                    );
                    if ($item['name'] == $chuPhong) {
                        array_unshift($lstAvatar, $new);
                    } else {
                        array_push($lstAvatar, $new);
                    }

                }
            }
        }
        return array('isGame' => false, 'chuphong' => $chuPhong, 'lstavt' => $lstAvatar);
    }

    public function getCreditByUsername($username)
    {
        $sql = 'SELECT user_credit FROM tb_user WHERE user_name = \'' . $username . '\'';
        $this->db->connect_db();
        $query = mysqli_query($this->db->conn, $sql);
        $row = mysqli_fetch_assoc($query);
        return $row['user_credit'];
    }

    public function checkDangKi($username, $password, $avatar)
    {
        if ($this->checkUsername($username) == false) {
            return 2; // username khong hop le
        }

        $this->db->connect_db();
        $sql = 'SELECT * FROM tb_user WHERE user_name = \'' . $username . '\'';
        $query = mysqli_query($this->db->conn, $sql);
        $result = mysqli_num_rows($query);
        if ($result > 0) {
            return 1; //tài khoản tồn tại
        } else {
            $sql = 'INSERT INTO `tb_user`(`user_name`, `avatar`, `user_credit`, `user_sex`, `password`) VALUES (\'' . $username . '\',\'' . $avatar . '\',50000,1,\'' . md5($password) . '\')';
            $query = mysqli_query($this->db->conn, $sql);
            if ($query == true) {
                return 0;
            } else {
                return 3; // lổi
            }
        }
    }

    public function checkUsername($username)
    {
        $check = preg_match('/^[a-zA-Z1-9]+$/', $username);
        if ($check) {
            return true;
        } else {
            return false;
        }
    }
}
