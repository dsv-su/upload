<?php

class Ldap {
    public function __construct($server, $base) {
        $this->conn = ldap_connect($server);
        $this->base_dn = $base;
        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_bind($this->conn);
    }

    private function search($term, ...$attributes) {
        $result = ldap_search($this->conn, $this->base_dn,
                              $term, $attributes);
        return ldap_get_entries($this->conn, $result);
    }

    public function get_name($uid) {
        $data = $this->search("uid=$uid", 'cn', 'uid');
        if($data['count'] !== 1) {
            $m = "LDAP search for '$uid' did not return exactly one result";
            throw new Exception($m);
        }
        return $data[0]['cn'][0];
    }

    public function get_users($search) {
        $out = array();
        $results = $this->search("(|(sn=$search*)(givenName=$search*))",
                                 'cn', 'uid');
        foreach($results as $result) {
            if($result['uid'][0]) {
                $out[] = array('user' => $result['uid'][0],
                               'name' => $result['cn'][0]);
            }
        }
        return $out;
    }
}

?>
