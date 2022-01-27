<?php

namespace App\Database;

use mysqli;

class Database
{
    private $db_host = "localhost";
    private $db_user = "softjoe";
    private $db_pass = "softjoe123";
    private $db_name = "blog_project";

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
