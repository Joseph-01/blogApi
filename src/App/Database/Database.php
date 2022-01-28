<?php

namespace App\Database;

use mysqli;
mysql://:@/heroku_2b3c8f938e792ff?reconnect=true
class Database
{
    private $db_host = "us-cdbr-east-05.cleardb.net";
    private $db_user = "heroku_2b3c8f938e792ff";
    private $db_pass = "bfdecd2f";
    private $db_name = "b4a22337e0a6e6";

    private $conn;
    // protected static $queryResult;

    public function __construct()
    {
        $this->connection();
    }

    public function connection()
    {
        $this->conn = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
        if($this->conn->connect_error) {
            die(json_encode(["ErrorMessage" => $this->conn->connect_error,
            "ErrorNo" => $this->conn->connect_errno]));
        }
        return $this->conn;
    }

    public function query($sql)
    {
        return $this->connection()->query($sql);
    }

    public function escape_string($string="")
    {
        return $this->connection()->escape_string($string);
    }

    public function insert_id()
    {
        return $this->conn->insert_id;
    }

    public function affected_rows()
    {
        return $this->conn->affected_rows;
    }
}
