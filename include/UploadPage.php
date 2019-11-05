<?php
class UploadPage {
    private $parts;
    private $uuid = '';
    private $item;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->parts = get_fragments('../include/upload.html');
        if(isset($_GET['ul'])) {
            $this->uuid = $_GET['ul'];
        }
        if(isset($_POST['uuid'])) {
            $this->uuid = $_POST['uuid'];
        }
        try {
            $this->item = $db->get_item($this->uuid);
        } catch(Exception $e) {
            error_log($e->getMessage());
            $this->item = null;
        }
    }
    
    public function render() {
        $message = '';
        $error = 'hidden';
        if(isset($_COOKIE['error'])) {
            $message = $_COOKIE['error'];
            setcookie('error');
        }
        if($message) {
            $error = 'visible';
        }
        print(replace(array('title' => 'DSV upload service',
                            'error' => $error,
                            'message' => $message,
                            'content' => $this->build_content()),
                      $this->parts['base']));
    }

    private function build_content() {
        if($this->uuid === '') {
            return replace(array('message' => 'No upload ID provided'),
                           $this->parts['message']);
        }
        if($this->item === null) {
            return replace(array('message' => 'Invalid upload ID'),
                           $this->parts['message']);
        }
        switch($this->item->get_state()) {
            case Item::COMP:
                return replace(array('message' => 'Upload done!'),
                               $this->parts['message']);
            case Item::PRUN:
                $message = 'This link has expired.';
                return replace(array('message' => $message),
                               $this->parts['message']);
            case Item::PEND:
                return replace(array('uuid' => $this->uuid),
                               $this->parts['form']);
            default:
                return replace(array('message' => 'Unknown error'),
                               $this->parts['message']);
        }
    }
}
?>
