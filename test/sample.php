<?php
echo "hohogehoge\n";

$_REQUEST['hoge'] = 'fooooooo';

eval(Debugger::get());
var_dump($_REQUEST);

if (array_key_exists('id', $_REQUEST)) {
    echo "id = ${id} !!!!\n";
} else {
    $null->hogehoghoge();
}

