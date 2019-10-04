<?php
class Item {
    private $id = '';
    private $description;
    private $state;
    
    public function __construct($id, $description, $state) {
        $this->id = $id;
        $this->description = $description;
        $this->state = $state;
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

    public function get_url() {
        $urlbase = preg_replace('/\/(index.php)?$/', '', $_SERVER['SCRIPT_URI']);
        switch($this->state) {
            case 'pending':
            case 'pruned':
                return $urlbase.'/link/?ul='.$this->id;
            case 'complete':
                return $urlbase.'?dl='.$this->id;
            default:
                throw new Exception('Invalid item state');
        }
    }
}
?>
