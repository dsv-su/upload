<?php

spl_autoload_register(function ($class) {
    include('../include/'.$class.'.php');
});
require('../config.php');
require('../include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$db = new Db(config\DB_HOST, config\DB_USER,
             config\DB_PASS, config\DB_NAME);
$ldap = new Ldap(config\LDAP_SERVER, config\BASE_DN);


if(isset($_POST['uuid'])) {
    $uuid = $_POST['uuid'];
    $result = $db->save_file($uuid, $_FILES['uploadfile']);
    if($result['state'] !== 'success') {
        setcookie('error', $result['message']);
    } else {
        notify_upload($db->get_item($uuid), $ldap);
    }
    header('Location: .?ul='.$uuid, true, 303);
}

$page = new UploadPage();
$page->render();

?>
