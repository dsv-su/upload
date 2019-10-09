<?php

spl_autoload_register(function ($class) {
    include('./include/'.$class.'.php');
});
require('./config.php');
require('./include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$db = new Db();

if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'new':
            $db->create_item($_GET['description']);
            header('Location: .', true, 303);
            break;
        case 'dl':
            $db->get_file($_GET['uuid']);
            exit(0);
        default:
            print('Unknown action.');
            exit(1);
    }
}

$page = new AdminPage();
$page->render();

?>
