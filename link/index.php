<?php

spl_autoload_register(function ($class) {
    include('../include/'.$class.'.php');
});
require('../config.php');
require('../include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$db = new Db();

if(isset($_POST['uuid'])) {
    $uuid = $_POST['uuid'];
    $result = $db->save_file($uuid, $_FILES['uploadfile']);
    if($result['state'] !== 'success') {
        setcookie('error', $result['message']);
    } else {
        notify($db->get_item($uuid));
    }
    header('Location: .?ul='.$uuid, true, 303);
}

$page = new UploadPage();
$page->render();

?>
