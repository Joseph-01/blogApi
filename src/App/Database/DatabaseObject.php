<?php

namespace App\Database;

use App\Database\Database;

use mysqli;

class DatabaseObject
{
    private static $database;
    public static $result;
    public static $session = false;
    protected static $tableName;
    protected static $dbColumns;



    /**
     * @param database is holding the returned value of the database connection
     */
    public static function setDatabase($database)
    {
        self::$database = $database;
    }

    /**
     * @param  [type] String [description] MySQL query string
     * @return [type] Array  [description] pulls the object attributes {object} into an array
     */
    public static function findBySql($sql)
    {
        self::$result = self::$database->query($sql);
        if (!self::$result) {
            die(json_encode(["ErrorMessage" => "Database query failed"]));
        }
        //Creates an empty array for object attributes {object}
        $object_array = [];

        //Fetch database resultSet into an associative array
        while ($record = self::$result->fetch_assoc()) {
            $object_array[] = self::instantiate($record);
        }

        self::$result->free();
        return $object_array;
    }

    public static function instantiate($record)
    {
        $object = new static;

        foreach ($record as $property => $value) {
            if (property_exists($object, $property)) {
                $object->$property = $value;
            }
        }

        return $object;
    }

    /**
     * FindAll records from database
     * @return [type] [description]
     */
    public static function findAll()
    {
        return static::findBySql("SELECT * FROM " . static::$tableName);
    }

    /**
     * [Find records By Id]
     * @param  [type] Int [description] Id of the record to be looked up
     * @return [type] Object or Boolean [description] object record found or false if none found
     */
    public static function findById($id)
    {
        //Gets an array of objects from findBySql function
        $result = static::findBySql("SELECT * FROM " . static::$tableName . " WHERE id='" . self::$database->escape_string($id) . "' LIMIT 1");
        //turn $result [type] object into $result [type] array using type casting
        $resultArray = (array)$result;
        //if the resultArray is not empty, pull out the first element(object) otherwise return false
        return !empty($resultArray) ? array_shift($resultArray) : false;
    }

    /**
     * [findAll records from database]
     * @return [type] [description]
     */
    public static function findAllByOrder($tableColumn = "created_at", $order = "DESC")
    {
        return static::findBySql("SELECT * FROM " . static::$tableName . " ORDER BY {$tableColumn} {$order}");
    }

    /**
     * [findAll records from database by user_id]
     * @return [type] [description]
     */
    public static function findByUserId($user_id, $tableColumn = "created_at", $order = "DESC")
    {
        $sql  = "SELECT * FROM " . static::$tableName;
        $sql .= " WHERE user_id= '" . self::$database->escape_string($user_id) . "' ";
        $sql .= "ORDER BY {$tableColumn} {$order}";
        return static::findBySql($sql);
    }

    /**
     * [findAll records from database by post_id]
     * @return [type] [description]
     */
    public static function findByPostId($post_id)
    {
        $sql  = "SELECT * FROM " . static::$tableName;
        $sql .= " WHERE post_id= '" . self::$database->escape_string($post_id) . "' ";
        return static::findBySql($sql);
    }

    /**
     * [findAll records from database by category]
     * @return [type] [description]
     */
    public static function findByCategory($category, $tableColumn = "created_at", $order = "DESC")
    {
        $sql  = "SELECT * FROM " . static::$tableName;
        $sql .= " WHERE category= '" . self::$database->escape_string($category) . "' ";
        $sql .= "ORDER BY {$tableColumn} {$order}";
        return static::findBySql($sql);
    }

    /**
     * [check if new email has already been used]
     * @return [type] boolean
     */
    public static function checkUserEmail($email)
    {
        $sql = "SELECT email FROM " . static::$tableName;
        $sql .= " WHERE email = '" . self::$database->escape_string($email) . "' ";
        $result = static::findBySql($sql);
        if(!empty($result)) {
            $resultArray = (array)$result;
            //if the resultArray is not empty, pull out the first element(object) otherwise return false
            return !empty($resultArray) ? array_shift($resultArray) : false;
        } else {
            return false;
        }
    }

    /**
     * [check username and password for login]
     * 
     */
    public static function loginUser($email, $password)
    {
        $sql = "SELECT * FROM " . static::$tableName;
        $sql .= " WHERE email = '" . self::$database->escape_string($email) . "' ";
        $sql .= "AND password = '" . self::$database->escape_string($password) . "' ";
        static::$result = static::findBySql($sql);
        if(!empty(static::$result)) {
            return static::$result;
        } else {
            return false;
        }
    }


    public static function checkLikes($post_id, $user_id)
    {
        $sql = "SELECT * FROM " . static::$tableName;
        $sql .= " WHERE post_id = '" . self::$database->escape_string($post_id) . "' ";
        $sql .= "AND user_id = '" . self::$database->escape_string($user_id) . "' ";
        $result = static::findBySql($sql);
        if (!empty($result)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Properties which have database columns, excluding ID
     * @return [type] Array [description] an assoc array of object properties which have database columns and their values values
     */
    public function attributes()
    {
        $attributes = [];
        foreach (static::$dbColumns as $column) {
            // we dont need the id column of the database since it is auto incremented

            if ($column == 'id') {
                continue;
            }
            $attributes[$column] = $this->$column;
        }
        return $attributes;
    }

    /**
     * A sanitized version of @attributes() that has been escaped to prevent SQl Injection
     */
    protected function sanitizedAttributes()
    {
        $sanitized = [];
        foreach ($this->attributes() as $key => $value) {
            $sanitized[$key] = self::$database->escape_string($value);
        }
        return $sanitized;
    }

    /**
     * Used for UPDATE operation, assigns the value of form fields to object properties value
     * @param [type] Array [description] an associative array containing object keys and value
     */
    public function mergeAttributes($args = [])
    {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Creates a record in the database by taking in the associative array of @sanitizedAttribute()
     * @return [type] Boolean [description] returns true if successful or false otherwise
     */
    public function create()
    {

        // Don't forget your SQL syntax and good habits:
        // - INSERT INTO table (key, key) VALUES ('value', 'value')
        // - single-quotes around all values
        // - escape all values to prevent SQL injection

        $attributes = $this->sanitizedAttributes();

        $sql = "INSERT INTO " . static::$tableName . " (";
        $sql .= join(', ', array_keys($attributes));
        $sql .= ") VALUES ('";
        $sql .= join("', '", array_values($attributes));
        $sql .= "')";
        self::$result = self::$database->query($sql);

        if (self::$result) {
            $this->id = self::$database->insert_id();
            return true;
        }

        return false;
    }

    /**
     * Updates a record in the database by taking in the associative array of @sanitizedAttribute() 
     * @return [type] Boolean [description] returns true if successful or false otherwise
     */
    public function update()
    {

        // converts the assoc array from @sanitizedAttributes to a normal array 
        $attributes = $this->sanitizedAttributes();
        $attribute_pairs = [];
        foreach ($attributes as $key => $value) {
            $attribute_pairs[] = "{$key}='{$value}'";
        }

        $sql = "UPDATE " . static::$tableName . " SET ";
        $sql .= join(', ', $attribute_pairs);
        $sql .= " WHERE id='" . self::$database->escape_string($this->id) . "' ";
        $sql .= "LIMIT 1";

        // send query
        self::$database->query($sql);

        return (self::$database->affected_rows() == 1) ? true : false;
    }

    public function save()
    {

        // A new record will not have an ID yet
        if (isset($this->id)) {

            return $this->update();
        } else {

            return $this->create();
        }
    }

    public function delete()
    {

        //   Don't forget your SQL syntax and good habits:
        // - DELETE FROM table WHERE condition LIMIT 1
        // - escape all values to prevent SQL injection
        // - use LIMIT 1

        $sql = "DELETE FROM " . static::$tableName . " ";
        $sql .= "WHERE id='" . self::$database->escape_string($this->id) . "' ";
        $sql .= "LIMIT 1";

        // send query
        self::$database->query($sql);

        return (self::$database->affected_rows() == 1) ? true : false;

        // After deleting, the instance of the object will still
        // exist, even though the database record does not.
        // This can be useful, as in:
        //   echo $user->first_name . " was deleted.";
        // but, for example, we can't call $user->update() after
        // calling $user->delete().
    }

    public static function pagination()
    {
        $page = $_GET["page"] ?? 1;

        $limitPerPage = 2;

        $sql = "SELECT count(id) as totalNumOfRecords from " . static::$tableName;
        self::$result = self::$database->query($sql);

        $rowsReturn = self::$result->fetch_assoc();
        $count = $rowsReturn["totalNumOfRecords"];
        $numOfPages = ceil($count / $limitPerPage);

        if ($numOfPages == 0) {
            $numOfPages = 1;
        }

        http_response_code(404);
        if ($page > $numOfPages || $page == 0) {
            $statusError = http_response_code(404);
            echo json_encode([
                "errorMessage" => "page not found",
                "status_code" => $statusError
            ]);
            exit;
        }

        $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));

        $sql = static::findBySql("SELECT * FROM " . static::$tableName . " LIMIT {$offset},{$limitPerPage}");
        $rowCount = count((array)$sql);

        $returnData = array();
        $returnData['rowsPerPage'] = $rowCount;
        $returnData['totalResults'] = $count;
        $returnData['totalPages'] = $numOfPages;
        ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
        ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);

        $returnData["data"] = $sql;
        return $returnData;
    }

    public static function login($email, $password)
    {
        if(static::$session == false) {
            $user = static::loginUser($email, $password);
            return $user;
        } 
    }
}
