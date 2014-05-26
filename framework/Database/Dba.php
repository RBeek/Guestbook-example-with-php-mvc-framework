<?php namespace Framework\Database;

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - __set()
 * - conn()
 * - dbSelect()
 * - rawQuery()
 * - dbJoin()
 * - dbInsert()
 * - lastInsertedId()
 * - dbUpdate()
 * - dbUpdateKey()
 * - update()
 * - dbDelete()
 * - order()
 * - and_condition()
 * - setfields()
 * - wherefields()
 * Classes list:
 * - Dba
 */
class Dba
{
    private $db;
    private $insertedId = FALSE;
    private $sql = "";

    function __construct($db = null)
    {
        if (!is_null($db)) {
            $this->db = $db;
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'username':
                $this->username = $value;
                break;

            case 'password':
                $this->password = $value;
                break;

            case 'dsn':
                $this->dsn = $value;
                break;

            default:
                throw new \Exception("$naam is ongeldig");
        }
    }

    /**
     *
     * @set connection to the database
     *
     * @Throws PDOException on failure
     *
     */
    public function conn()
    {
        if (!$this->db instanceof \PDO) {
            $this->db = new \PDO($this->dsn, $this->username, $this->password);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    /***
     *
     * @select values from a table
     *
     * @access public
     *
     * @param string $table Name of table
     *
     * @param string $fieldname
     *
     * @param string $id
     *
     * @return an array on succes or throw a PDOException on failure
     *
    */
    public function dbSelect($table, $fieldname = null, $id = null)
    {
        $this->conn();
        $sql = $this->sql ? "SELECT * FROM $table WHERE $fieldname =:id " . $this->sql : "SELECT * FROM $table WHERE $fieldname =:id";
        $this->sql = "";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *
     * @execute raw query
     *
     * @param string The query to be executed
     *
     */
    public function rawQuery($sql)
    {
        $this->conn();
        return $this->db->query($sql);
    }

    /**
     *
     * @select values from multiple tables
     *
     * @access public
     *
     * @param string $sql
     *
     * @return array
     *
     */
    public function dbJoin($sql, $values)
    {
        $this->conn();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *
     * @Insert value into table
     *
     * @acces public
     *
     * @param string $table
     *
     * @param array $values  e.g: array( array( $key=>$value, etc)  )
     *
     * @return int  Insert Id on succes, or throw a PDOexeption
     *
     */
    public function dbInsert($table, $values)
    {
        $this->conn();

        /*** snarg the field names from the first array member ***/
        $fieldnames = array_keys($values[0]);

        /*** now build the query ***/
        $size = sizeof($fieldnames);
        $i = 1;
        $sql = "INSERT INTO $table";

        /*** set the field names ***/
        $fields = '( ' . implode(' ,', $fieldnames) . ' )';

        /*** set the placeholders ***/
        $bound = '(:' . implode(', :', $fieldnames) . ' )';

        /*** put the query together ***/
        $sql.= $fields . ' VALUES ' . $bound;

        // echo "$sql";
        // die();
        /*** prepare en execute ***/
        $stmt = $this->db->prepare($sql);
        foreach ($values as $vals) {
            $inserted = $stmt->execute($vals);
        }
        $this->insertedId = $this->db->lastInsertID();
    }

    public function lastInsertedId()
    {
        $id = $this->insertedId;
        $this->insertedId = FALSE;
        return $id;
    }

    /**
     *
     * @Update a value in the table
     *
     * @access public
     *
     * @param string $table
     *
     * @param string $fieldname
     *
     * @param string $value The new value
     *
     * @param string $pk The primary key
     *
     * @param string $id id number
     *
     * @throws PDOException on failure
     *
     */
    public function dbUpdate($table, $fieldname, $value, $pk, $id)
    {
        $this->conn();
        $sql = "UPDATE `$table` SET `$fieldname`='{$value}' WHERE `$pk` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
        $stmt->execute();
    }

    public function dbUpdateKey($table, $pk, $id, $values)
    {
        try {
            $this->conn();
            $sql = "UPDATE `$table` SET value = :value WHERE `$pk` = $id AND key = :key";
            $stmt = $this->db->prepare($sql);

            foreach ($values as $vals) {
                $stmt->bindParam(':key', $vals[0], \PDO::PARAM_STR);
                $stmt->bindParam(':value', $vals[1], \PDO::PARAM_STR);
                $ret = $stmt->execute();
            }
        }
        catch(Exception $e) {
        }
    }

    public function update($table, $pk, $id, $values = null, $conditions = null)
    {
        try {

            $sql = "UPDATE $table SET \n";

            //the values
            $sql.= join(",", array_map(array('Dba', 'setfields'), array_keys($values)));

            // if conditions
            if (!is_null($conditions)) {
                $sql.= " WHERE " . join(" AND ", array_map(array('Dba', 'wherefields'), array_keys($conditions)));
            } else {

                // set primary key in conditions array
                $conditions[$pk] = $id;
                $sql.= " WHERE $pk = :where$pk";
            }

            $stmt = $this->db->prepare($sql);

            $params = array();
            foreach ($values as $field => $value) {
                $params[":set$field"] = $value;
            }

            // add conditions to params array
            // ELSE, add id
            foreach ($conditions as $field => $value) {
                $params[":where$field"] = $value;
            }

            $stmt->execute($params);

            // return changed rows count
            return $stmt->rowCount();
        }
        catch(\Exception $e) {

            // $e->getMessage();


        }
    }

    /**
     *
     * @Remove a record from the table
     *
     * @access public
     *
     * @param string $table
     *
     * @param string $fieldname
     *
     * @param string $ids //or array
     *
     * @throws PDOexception on failure
     *
     */
    public function dbDelete($table, $fieldname, $ids)
    {
        $this->conn();
        $sql = "DELETE FROM `$table` WHERE `$fieldname` = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $stmt->execute();
            }
        } else {
            $id = $ids;
            $stmt->execute();
        }
    }

    /**
     *
     * Add order
     *
     * @param string $fieldnaam
     *
     * @param string $order
     *
     */
    public function order($fieldnaam, $order = 'ASC')
    {
        $this->sql.= " ORDER BY $fieldnaam $order";
    }

    public function and_condition($field, $value, $operator = '=')
    {
        $this->sql.= " AND $field " . $operator . " $value";
    }

    /**
     * @helperfunctions used as callbacks
     *
     * @param string $field De table column/field
     *
     */
    function setfields($field)
    {
        return "$field = :set$field";
    }
    function wherefields($field)
    {
        return "$field = :where$field";
    }
}
