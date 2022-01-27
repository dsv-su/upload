<?php

/*
   Takes an html file containing named fragments.
   Returns an associative array on the format array[name]=>fragment.
   
   Fragments are delimited like this:
   
   ¤¤ name 1 ¤¤
   fragment 1
   ¤¤ name 2 ¤¤
   fragment 2
   ¤¤ name 3 ¤¤
   fragment 3

   The first delimiter and name ('¤¤ name 1 ¤¤' in the above example) can
   be omitted, in which case the first fragment will be assigned the
   name 'base'. All other fragments must be named.

   Throws an exception if:
   - any fragment except the first is missing a name
   - two (or more) fragments share a name
 */
function get_fragments($infile) {
    $out = array();
    $name = '';
    $current_fragment = '';

    $filecontents = file($infile);
    $iter = 0;
    foreach($filecontents as $line) {
        if(strpos(trim($line), '¤¤') === 0) {
            if($iter != 0) {
                $out = try_adding($name, $current_fragment, $out, $infile);
            }
            $name = trim($line, "\t\n\r ¤");
            $current_fragment = '';
        } else {
            $current_fragment .= $line;
        }
        $iter++;
    }
    return try_adding($name, $current_fragment, $out, $infile);
}

function try_adding($key, $value, $array, $filename) {
    if(array_key_exists($key, $array)) {
        throw new Exception('There is already a fragment with that name in '
                           .$filename);
    } else if($key === '') {
        throw new Exception('There is an unnamed fragment in '.$filename);
    }
    
    $array[$key] = trim($value);

    return $array;
}

/*
   Takes an associative array and a string.
   Returns a string.

   Replaces each occurrence of each array key in the input string
   with the associated array value, and returns the result.
 */
function replace($assoc_arr, $subject) {
    $keys = array();
    $values = array();

    foreach($assoc_arr as $key => $value) {
        $keys[] = '¤'.$key.'¤';
        $values[] = $value;
    }

    return str_replace($keys, $values, $subject);
}

function get_user() {
    if(isset($_SERVER['REMOTE_USER'])) {
        return preg_replace('/@.*$/',
                            '',
                            $_SERVER['REMOTE_USER']);
    }
    return null;
}

function dateFromTimestamp($ts) {
    if($ts !== null) {
        return DateTime::createFromFormat('U', $ts);
    }
    return null;
}

function tsDaysInFuture($days) {
    $now = new DateTime();
    $now->add(new DateInterval('P'.$days.'D'));
    return $now->getTimestamp();
}

// Taken from this link:
// http://www.php.net/manual/en/function.uniqid.php#94959
function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    // 32 bits for "time_low"
                    mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0xffff ),
                    
                    // 16 bits for "time_mid"
                    mt_rand( 0, 0xffff ),
                    
                    // 16 bits for "time_hi_and_version",
                    // four most significant bits holds version number 4
                    mt_rand( 0, 0x0fff ) | 0x4000,
                    
                    // 16 bits, 8 bits for "clk_seq_hi_res",
                    // 8 bits for "clk_seq_low",
                    // two most significant bits holds zero and one for variant DCE1.1
                    mt_rand( 0, 0x3fff ) | 0x8000,
                    
                    // 48 bits for "node"
                    mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0xffff )
    );
}

function notify($item) {
    $ldap = ldap_connect('ldaps://ldap.su.se');
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_bind($ldap);
    
    $uid = $item->get_owner();
    $response = ldap_search($ldap, 'dc=su,dc=se', "uid=$uid", ['mail']);
    $result = ldap_get_entries($ldap, $response);
    if($result['count'] !== 1) {
        error_log("LDAP search for '$uid' did not return exactly one result");
        error_log("No email will be sent for upload ".$item->get_uuid());
        return;
    }
    $email = $result[0]['mail'][0];

    $description = $item->get_description();
    $subject = "[DSV upload] $description har laddats upp";
    $message = <<<END
En fil har laddats upp till din uppladdningslänk "$description".
Du kan ladda ner filen från https://upload.dsv.su.se.
END;

    mb_send_mail($email, $subject, $message, "From: noreply-upload@dsv.su.se");
}
?>
