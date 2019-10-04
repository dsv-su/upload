<?php

class Db {
    private $db;

    public function __construct() {
        global $db_host, $db_user, $db_pass, $db_name;
        $this->db = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if($this->db->connect_errno) {
            $error = 'Failed to connect to db. The error was: '
                    .$this->db->connect_error;
            throw new Exception($error);
        }
    }

    public function prepare($statement) {
        if(!($s = $this->db->prepare($statement))) {
            $error  = 'Failed to prepare the following statement: '
                     .$statement;
            $error .= '\n';
            $error .= $this->db->error.' ('.$this->db->errno.')';
            throw new Exception($error);
        }
        
        return $s;
    }

    public function begin_trans() {
        $this->db->begin_transaction(
            MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
    }

    public function commit_trans() {
        $this->db->commit();
        return true;
    }

    public function revert_trans() {
        $this->db->rollback();
        return false;
    }

    public static function execute($statement) {
        if(!$statement->execute()) {
            $error  = 'Failed to execute statement.';
            $error .= '\n';
            $error .= $statement->error.' ('.$statement->errno.')';
            throw new Exception($error);
        }
        return true;
    }

    public static function result_list($statement) {
        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function result_single($statement) {
        $out = result_list($statement);
        switch(count($out)) {
            case 0:
                return null;
            case 1:
                foreach($out as $value) {
                    return $value;
                }
            default:
                throw new Exception('More than one result available.');
        }
    }

    public function get_items($user, $state) {
        switch($state) {
            case 'pending':
            case 'complete':
            case 'pruned':
                break;
            default:
                throw new Exception('Invalid item state in get_items: '.$state);
        }
        $stmt = $this->prepare('select * from `items` 
                                where `owner`=? and `state`=?');
        $stmt->bind_param('ss', $user, $state);
        $stmt->execute();

        $out = array();
        foreach($this->result_list($stmt) as $row) {
            $out[] = new Item($row);
        }
        return $out;
    }
}
?>
