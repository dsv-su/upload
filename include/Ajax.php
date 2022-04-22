<?php

class Ajax {
    public function __construct($db, $ldap) {
        $this->db = $db;
        $this->ldap = $ldap;
        $this->parts = get_fragments('./include/admin.html');
    }

    public function process() {
        header('Content-type: text/json');

        $result = array();
        $status = 0;
        switch($_GET['action']) {
            case 'new':
                $this->db->create_item($_GET['description']);
                header('Location: .', true, 303);
                break;
            case 'dl':
                $this->db->get_file($_GET['uuid']);
                break;
            case 'share':
                $result = $this->share($_POST['item'], $_POST['user']);
                break;
            case 'unshare':
                $result['ok'] = $this->db->unshare_item($_POST['item'],
                                                        $_POST['user']);
                break;
            case 'suggest':
                $suggestions = $this->ldap->get_users($_POST['input']);
                $result['suggestions'] = $suggestions;
                $result['ok'] = true;
                break;
            default:
                $result['error'] = 'Unknown action.';
                $status = 1;
        }
        if($result) {
            print(json_encode($result));
        }
        return $status;
    }

    private function share($uuid, $user) {
        $result = array('ok' => false);
        $name = $user;
        try {
            $name = $this->ldap->get_name($user);
        } catch(Exception $e) {
            $result['error'] = 'Unknown user';
            return $result;
        }
        $success = $this->db->share_item($uuid, $user);
        $result['ok'] = $success;
        if(!$success) {
            $result['error'] = 'Failed to share item';
            return $result;
        }
        $result['html'] = replace(array('uuid' => $uuid,
                                        'user' => $user,
                                        'name' => $name),
                                  $this->parts['shared_user']);
        return $result;
    }
}

?>
