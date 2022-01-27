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
        $this->update_states();
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
        $out = self::result_list($statement);
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

    private function update_states() {
        global $delete_time, $purge_time;

        $deletefiles = array();
        try {
            $this->begin_trans();
            $stmt = $this->prepare('select * from `items`');
            self::execute($stmt);
            foreach(self::result_list($stmt) as $row) {
                $item = new Item($row);
                $uuid = $item->get_uuid();
                $state = $item->get_state();
                $ttl = $item->get_ttl();
                $now = time();
                if($ttl <= 0) {
                    switch($state) {
                        case Item::PEND:
                            $newstate = Item::PRUN;
                            $newend = tsDaysInFuture($purge_time);
                            $stmt = $this->prepare('update `items` set
                                                        `state`=?,
                                                        `end_time`=?
                                                    where `uuid`=?');
                            $stmt->bind_param('sis', $newstate, $newend, $uuid);
                            self::execute($stmt);
                            break;
                        case Item::COMP:
                            $newstate = Item::PRUN;
                            $newend = tsDaysInFuture($purge_time);
                            $stmt = $this->prepare('update `items` set
                                                        `state`=?,
                                                        `end_time`=?
                                                    where `uuid`=?');
                            $stmt->bind_param('sis', $newstate, $newend, $uuid);
                            self::execute($stmt);
                            $deletefiles[] = $uuid;
                            break;
                        case Item::PRUN:
                            $stmt = $this->prepare('delete from `items`
                                                    where `uuid`=?');
                            $stmt->bind_param('s', $uuid);
                            self::execute($stmt);
                            break;
                        default:
                            throw new Exception('Invalid state for Item '
                                               .$uuid.': '.$state
                                               .' in Db constructor');
                    }
                }
            }
            $this->commit_trans();
        } catch(Exception $e) {
            $this->revert_trans();
            throw $e;
        }
        global $files_dir;
        foreach($deletefiles as $uuid) {
            $filepath = $files_dir.'/'.$uuid;
            if(file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function get_item($uuid) {
        $stmt = $this->prepare('select * from `items` where `uuid`=?');
        $stmt->bind_param('s', $uuid);
        self::execute($stmt);
        $result = self::result_single($stmt);
        return new Item($result);
    }
    
    public function get_items($user, $state) {
        switch($state) {
            case Item::PEND:
            case Item::COMP:
            case Item::PRUN:
                break;
            default:
                throw new Exception('Invalid item state in get_items: '.$state);
        }
        $stmt = $this->prepare('select * from `items` 
                                where `owner`=? and `state`=?');
        $stmt->bind_param('ss', $user, $state);
        self::execute($stmt);

        $out = array();
        foreach(self::result_list($stmt) as $row) {
            $out[] = new Item($row);
        }
        return $out;
    }

    public function create_item($description) {
        global $valid_time;
        try {
            $this->begin_trans();
            $description = htmlspecialchars($description);
            $owner = get_user();
            $stmt = $this->prepare('select `uuid` from `items`');
            self::execute($stmt);
            $uuids = array();
            foreach(self::result_list($stmt) as $row) {
                $uuids[] = $row['uuid'];
            }
            $uuid = gen_uuid();
            while(in_array($uuid, $uuids)) {
                $uuid = gen_uuid();
            }
            $now = time();
            $end = tsDaysInFuture($valid_time);
            $stmt = $this->prepare('insert into `items` (
                                        `uuid`, `owner`,
                                        `description`, `create_time`, `end_time`
                                    ) values (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssii', $uuid, $owner, $description, $now, $end);
            self::execute($stmt);
            $this->commit_trans();
            return $this->get_item($uuid);
        } catch(Exception $e) {
            $this->revert_trans();
            throw $e;
        }
    }

    public function save_file($uuid, $file) {
        $result = array('state' => 'error');
        $item = null;
        try {
            $item = $this->get_item($uuid);
        } catch(Exception $e) {
            $result['message'] = 'Unknown upload ID.';
            return $result;
        }
        if($item->get_state() != Item::PEND) {
            $result['message'] = 'This link is in an invalid state.';
            return $result;
        }
        global $files_dir;
        $savepath = $files_dir.'/'.$uuid;
        if(file_exists($savepath)) {
            $result['message'] = 'This link cannot accept further uploads.';
            return $result;
        }
        if(!isset($file['error']) || is_array($file['error'])) {
            $result['message'] = "Invalid call.";
            return $result;
        }
        switch($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $result['message'] = 'No file was sent.';
                return $result;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $result['message'] = 'The chose file is too large.';
                return $result;
            default:
                $result['message'] = 'An unknown error has occurred.';
                return $result;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tmp_name = $file['tmp_name'];
        $mime = $finfo->file($tmp_name);
        global $formats;
        $extension = '';
        foreach($formats as $ext => $mimetype) {
            if($mimetype === $mime) {
                $extension = $ext;
                break;
            }
        }
        if(!$extension) {
            $result['message'] = 'Invalid file type. Permitted file types are:'
                                .'<br/>'.implode(', ', array_keys($formats));
            return $result;
        }
        if(!move_uploaded_file($tmp_name, $savepath)) {
            $result['message'] = 'The file could not be saved.';
            return $result;
        }
        $now = time();
        global $delete_time;
        $end = tsDaysInFuture($delete_time);
        $newstate = Item::COMP;
        $stmt = $this->prepare('update `items` set
                                    `state`=?,
                                    `upload_time`=?,
                                    `end_time`=?
                                where `uuid`=?');
        $stmt->bind_param('siis', $newstate, $now, $end, $uuid);
        self::execute($stmt);
        $result['state'] = 'success';
        return $result;
    }

    public function get_file($uuid) {
        $item = self::get_item($uuid);
        $user = get_user();
        if($item->get_owner() != $user) {
            print("Du har inte tillgång till den här filen.");
            exit(1);
        }
        if($item->get_state() != Item::COMP) {
            print("Den här filen är inte nedladdningsbar.");
            exit(1);
        }
        global $files_dir;
        $filepath = $files_dir.'/'.$uuid;
        if(!file_exists($filepath)) {
            print("Filen har försvunnit?!");
            exit(1);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filepath);
        global $formats;
        $extension = '';
        foreach($formats as $ext => $mimetype) {
            if($mimetype === $mime) {
                $extension = $ext;
                break;
            }
        }
        if(!$extension) {
            print('Filtypen känns inte igen: '.$mime);
            exit(1);
        }
        $filename = $item->get_description().'.'.$extension;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Expires: 0');
        header('Cache-Control: no-cache');
        header('Content-length: '.filesize($filepath));
        readfile($filepath);
        exit(0);
    }
}
?>
