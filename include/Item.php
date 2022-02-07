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
    private $end_time;
    private $state;
    
    public function __construct($db_row) {
        $this->uuid = $db_row['uuid'];
        $this->owner = $db_row['owner'];
        $this->description = $db_row['description'];
        $this->create_time = dateFromTimestamp($db_row['create_time']);
        $this->upload_time = dateFromTimestamp($db_row['upload_time']);
        $this->end_time = dateFromTimestamp($db_row['end_time']);
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

    public function get_endtime($format = null) {
        if($format === null) {
            return $this->end_time;
        }
        return $this->end_time->format($format);
    }

    public function get_ttl() {
        $now = new DateTime();
        $ttl = $now->diff($this->end_time);
        return $ttl->format('%r%a');
    }
    
    public function get_url() {
        switch($this->get_state()) {
            case Item::PEND:
            case Item::PRUN:
                return config\BASE_URL.'/link/?ul='.$this->uuid;
            case Item::COMP:
                return config\BASE_URL.'?action=dl&uuid='.$this->uuid;
            default:
                throw new Exception('Invalid state for Item '
                                   .$this->uuid.": $state in get_url");
        }
    }
}
?>
