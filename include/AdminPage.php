<?php
class AdminPage {
    private $base = array();
    private $parts = array();
    private $username = '';
    private $user_displayname = '';
    private $db = null;
    
    public function __construct() {
        $this->base = get_fragments('./include/base.html');
        $this->parts = get_fragments('./include/admin.html');
        if(isset($_SERVER['REMOTE_USER'])) {
            $this->username = preg_replace('/@.*$/', '', $_SERVER['REMOTE_USER']);
        }
        if(isset($_SERVER['displayName'])) {
            $this->user_displayname = $_SERVER['displayName'];
        }
        global $db;
        $this->db = $db;
    }

    public function render() {
        $userinfo = $this->user_displayname.' ('.$this->username.')';
        print(replace(array(
            'title' => 'DSV:s uppladdningstjänst',
            'user' => $userinfo
        ), $this->base['head']));
        print($this->parts['base']);
        $this->print_complete();
        $this->print_pending();
        $this->print_pruned();
        print($this->base['foot']);
    }

    private function print_complete() {
        $list = $this->db->get_items($this->username, 'complete');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'ttl' => $item->get_ttl(),
                                  'link' => $item->get_url()),
                            $this->parts['dl_item']);
        }
        print(replace(array('items' => $out), $this->parts['completed']));
    }

    private function print_pending() {
        $list = $this->db->get_items($this->username, 'pending');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'ttl' => $item->get_ttl(),
                                  'link' => $item->get_url()),
                            $this->parts['ul_item']);
        }
        print(replace(array('items' => $out), $this->parts['pending']));
    }

    private function print_pruned() {
        $list = $this->db->get_items($this->username, 'pruned');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'link' => $item->get_url()),
                            $this->parts['old_link_item']);
        }
        print(replace(array('items' => $out), $this->parts['pruned']));
    }
}
?>
