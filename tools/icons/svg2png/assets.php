<?php
    $file = urldecode($_SERVER['QUERY_STRING']);
    $extension = end(explode('.', $file));

    if ($extension === 'js') {
        header('Content-Type: text/javascript');
    } else if ($extension === 'css') {
        header('Content-Type: text/css');
    }
    
    readfile('../../../public/assets/' . $file);