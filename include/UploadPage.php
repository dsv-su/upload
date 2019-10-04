<?php
class UploadPage {
    private $parts = array();
    private $id = '';
    
    public function __construct() {
        $this->parts = get_fragments('../include/upload.html');
        if(isset($_GET['ul'])) {
            $this->id = $_GET['ul'];
        }
    }
    
    public function render() {
        $content = '';
        if($this->id) {
            $content = replace(array('id' => $this->id),
                               $this->parts['form']);
        } else {
            $content = replace(array('message' => "Uppladdnings-id saknas."),
                               $this->parts['error']);
        }
        print(replace(array('title' => 'DSV:s uppladdningstjänst',
                            'content' => $content),
                      $this->parts['base']));
    }
}
?>
