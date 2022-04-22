<?php

spl_autoload_register(function ($class) {
    include('./include/'.$class.'.php');
});
require('./config.php');
require('./include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$db = new Db(config\DB_HOST, config\DB_USER,
             config\DB_PASS, config\DB_NAME);
$ldap = new Ldap(config\LDAP_SERVER, config\BASE_DN);

if(isset($_GET['action'])) {
    $ajax = new Ajax($db, $ldap);
    $result = $ajax->process();
    exit($result);
}

$page = new AdminPage(config\SITE_NAME, $db, $ldap);
$page->render();

?>
