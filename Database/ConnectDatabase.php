<?php
require_once 'config.php';
class ConnectDatabase
{
    protected $conn;
    function ConnectDatabase(){
    }

    function connect_db(){
        $conf = new Config();
        if(!$this->conn){
            $this->conn = mysqli_connect($conf->host, $conf->userName, $conf->password, $conf->databaseName) or die ('Không thể kết nối tới database');
            mysqli_set_charset($this->conn, 'utf8');
        }
    }

    function disconnect_db(){
        if ($this->conn){
            mysqli_close($this->conn);
        }
    }
}