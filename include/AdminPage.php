<?php
class AdminPage {
    private $db;
    private $base;
    private $parts;
    private $username = '';
    private $user_displayname = '';

    public function __construct($name, $db, $ldap) {
        $this->name = $name;
        $this->db = $db;
        $this->ldap = $ldap;
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
            'title' => '['.$this->name.'] Administrera uppladdningslänkar',
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
            $uuid = $item->get_uuid();
            $sharing = '';
            if($item->get_owner() == $this->username) {
                $users = '';
                foreach($item->get_sharing() as $user) {
                    $name = $this->ldap->get_name($user);
                    $users .= replace(array('uuid' => $uuid,
                                            'user' => $user,
                                            'name' => $name),
                                      $this->parts['shared_user']);
                }
                $sharing = replace(array('uuid' => $uuid,
                                         'shared_users' => $users),
                                   $this->parts['sharing']);
            } else {
                $owner = $this->ldap->get_name($item->get_owner());
                $sharing = replace(array('owner' => $owner),
                                   $this->parts['shared']);
            }
            $out .= replace(array('name'    => $item->get_description(),
                                  'created' => $item->get_create_time('Y-m-d'),
                                  'ttl'     => $item->get_ttl(),
                                  'link'    => $item->get_url(),
                                  'sharing' => $sharing),
                            $this->parts['ul_item']);
        }
        print(replace(array('valid_time' => config\VALID_TIME,
                            'items'      => $out),
                      $this->parts['pending']));
    }

    private function print_completed() {
        $list = $this->db->get_items($this->username, 'completed');
        $out = '';
        foreach($list as $item) {
            $uuid = $item->get_uuid();
            $sharing = '';
            if($item->get_owner() == $this->username) {
                $users = '';
                foreach($item->get_sharing() as $user) {
                    $name = $this->ldap->get_name($user);
                    $users .= replace(array('uuid' => $uuid,
                                            'user' => $user,
                                            'name' => $name),
                                      $this->parts['shared_user']);
                }
                $sharing = replace(array('uuid' => $uuid,
                                         'shared_users' => $users),
                                   $this->parts['sharing']);
            } else {
                $owner = $this->ldap->get_name($item->get_owner());
                $sharing = replace(array('owner' => $owner),
                                   $this->parts['shared']);
            }
            $out .= replace(array('name'     => $item->get_description(),
                                  'uploaded' => $item->get_upload_time('Y-m-d'),
                                  'ttl'      => $item->get_ttl(),
                                  'link'     => $item->get_url(),
                                  'sharing'  => $sharing),
                            $this->parts['dl_item']);
        }
        print(replace(array('delete_time' => config\DELETE_TIME,
                            'items'       => $out),
                      $this->parts['completed']));
    }

    private function print_pruned() {
        $list = $this->db->get_items($this->username, 'pruned');
        $out = '';
        foreach($list as $item) {
            $sharing = '';
            if($item->get_owner() == $this->username) {
                $users = '';
                foreach($item->get_sharing() as $user) {
                    $name = $this->ldap->get_name($user);
                    $users .= replace(array('user' => $name),
                                      $this->parts['sharing_pruned_user']);
                }
                $sharing = replace(array('uuid' => $uuid,
                                         'users' => $users),
                                   $this->parts['sharing_pruned']);
            } else {
                $owner = $this->ldap->get_name($item->get_owner());
                $sharing = replace(array('owner' => $owner),
                                   $this->parts['shared']);
            }
            $out .= replace(array('name'    => $item->get_description(),
                                  'link'    => $item->get_url(),
                                  'sharing' => $sharing),
                            $this->parts['old_link_item']);
        }
        print(replace(array('purge_time' => config\PURGE_TIME,
                            'items'      => $out),
                      $this->parts['pruned']));
    }
}
?>
