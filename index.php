<?php

spl_autoload_register(function ($class) {
    include('./include/'.$class.'.php');
});
require('./config.php');
require('./include/functions.php');

header('Content-Type: text/html; charset=UTF-8');

$base = get_fragments('./include/base.html');

print(replace(array(
    'title' => 'DSV:s uppladdningstjänst'
), $base['head']));
print($base['foot']);

?>
