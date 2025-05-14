<?php
// fetch_logins.php
header('Content-Type: application/json; charset=UTF-8');
$file = __DIR__ . '/logins.json';

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        echo @file_get_contents($file) ?: '[]';
        break;

    case 'PUT':
        $body = file_get_contents('php://input');
        if ($body) {
            file_put_contents($file, $body);
            echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(['error'=>'Empty body'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error'=>'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
}
