<?php
class AdminPage {
    private $db;
    private $base;
    private $parts;
    private $username = '';
    private $user_displayname = '';
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->base = get_fragments('./include/base.html');
        $this->parts = get_fragments('./include/admin.html');
        $this->username = get_user();
        if(isset($_SERVER['displayName'])) {
            $this->user_displayname = $_SERVER['displayName'];
        }
    }

    public function render() {
        $userinfo = $this->user_displayname.' ('.$this->username.')';
        print(replace(array(
            'title' => 'DSV:s uppladdningstjänst',
            'user' => $userinfo
        ), $this->base['head']));
        print($this->parts['base']);
        $this->print_pending();
        $this->print_completed();
        $this->print_pruned();
        print($this->base['foot']);
    }

    private function print_pending() {
        $list = $this->db->get_items($this->username, 'pending');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'created' => $item->get_create_time('Y-m-d'),
                                  'ttl' => $item->get_ttl(),
                                  'link' => $item->get_url()),
                            $this->parts['ul_item']);
        }
        global $valid_time;
        print(replace(array('valid_time' => $valid_time,
                            'items' => $out),
                      $this->parts['pending']));
    }

    private function print_completed() {
        $list = $this->db->get_items($this->username, 'completed');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'uploaded' => $item->get_upload_time('Y-m-d'),
                                  'ttl' => $item->get_ttl(),
                                  'link' => $item->get_url()),
                            $this->parts['dl_item']);
        }
        global $delete_time;
        print(replace(array('delete_time' => $delete_time,
                            'items' => $out),
                      $this->parts['completed']));
    }

    private function print_pruned() {
        $list = $this->db->get_items($this->username, 'pruned');
        $out = '';
        foreach($list as $item) {
            $out .= replace(array('name' => $item->get_description(),
                                  'link' => $item->get_url()),
                            $this->parts['old_link_item']);
        }
        global $purge_time;
        print(replace(array('purge_time' => $purge_time,
                            'items' => $out),
                      $this->parts['pruned']));
    }
}
?>
