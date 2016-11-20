<?php

function autoload_bosher($class) {

    $filename = str_replace('\\', '/', $class);
    if(is_file($filename) . '.php') {
        require_once($filename . '.php');
        return;
    }
}

spl_autoload_register('autoload_bosher');

global $USER;
if($USER->IsAdmin() === false) {
    echo json_encode([
        'code' => '403',
        'message' => 'Authentication failed'
    ]);
    exit;
}