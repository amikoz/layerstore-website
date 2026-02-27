<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'OK',
    'php_version' => phpversion(),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'input' => file_get_contents('php://input')
]);
