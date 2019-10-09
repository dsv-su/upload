<?php
class Item {
    const COMP = 'completed';
    const PEND = 'pending';
    const PRUN = 'pruned';
    private $uuid;
    private $owner;
    private $description;
    private $create_time;
    private $upload_time;
    private $upload_deleted;
    private $state;
    
    public function __construct($db_row) {
        $this->uuid = $db_row['uuid'];
        $this->owner = $db_row['owner'];
        $this->description = $db_row['description'];
        $this->create_time = dateFromTimestamp($db_row['create_time']);
        $this->upload_time = dateFromTimestamp($db_row['upload_time']);
        $this->upload_deleted = dateFromTimestamp($db_row['upload_deleted']);
        $state = $db_row['state'];
        switch($state) {
            case Item::COMP:
            case Item::PEND:
            case Item::PRUN:
                $this->state = $state;
                break;
            default:
                throw new Exception('Invalid state for Item '
                                   .$this->uuid.": $state in constructor");
        }
    }
    
    public function get_uuid() {
        return $this->uuid;
    }

    public function get_owner() {
        return $this->owner;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_state() {
        return $this->state;
    }

    public function get_create_time($format = null) {
        if($format === null) {
            return $this->create_time;
        }
        return $this->create_time->format($format);
    }

    public function get_upload_time($format = null) {
        if($format === null) {
            return $this->upload_time;
        }
        return $this->upload_time->format($format);
    }

    public function get_ttl() {
        $interval = null;
        $base = null;
        switch($this->get_state()) {
            case Item::PEND:
                global $valid_time;
                $interval = new DateInterval('P'.$valid_time.'D');
                $base = $this->create_time;
                break;
            case Item::COMP:
                global $delete_time;
                $interval = new DateInterval('P'.$delete_time.'D');
                $base = $this->upload_time;
                break;
            case Item::PRUN:
                global $purge_time;
                $interval = new DateInterval('P'.$purge_time.'D');
                $base = $this->create_time;
                break;
            default:
                throw new Exception('Invalid state for Item '
                                   .$this->uuid.": $state in get_ttl");
        }
        $base->add($interval);
        $now = new DateTime();
        $ttl = $now->diff($base);
        return $ttl->format('%r%a');
    }
    
    public function get_url() {
        global $base_url;
        switch($this->get_state()) {
            case Item::PEND:
            case Item::PRUN:
                return $base_url.'/link/?ul='.$this->uuid;
            case Item::COMP:
                return $base_url.'?action=dl&uuid='.$this->uuid;
            default:
                throw new Exception('Invalid state for Item '
                                   .$this->uuid.": $state in get_url");
        }
    }
}
?>
