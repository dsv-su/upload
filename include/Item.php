<?php
class Item {
    private $id = '';
    private $description;
    private $state;
    private $create_time;
    
    public function __construct($db_row) {
        $this->id = $db_row['uuid'];
        $this->description = $db_row['description'];
        $this->state = $db_row['state'];
        $this->create_time = $db_row['create_time'];
    }

    public function get_id() {
        return $this->id;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_state() {
        return $this->state;
    }

    public function get_ttl() {
        return 5;
    }
    
    public function get_url() {
        global $base_url;
        switch($this->state) {
            case 'pending':
            case 'pruned':
                return $base_url.'/link/?ul='.$this->id;
            case 'complete':
                return $base_url.'?dl='.$this->id;
            default:
                throw new Exception('Invalid item state');
        }
    }
}
?>
