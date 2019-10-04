<?php

spl_autoload_register(function ($class) {
    include('./include/'.$class.'.php');
});
require('./config.php');
require('./include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$db = new Db();

$page = new AdminPage();
$page->render();

?>
