<?php

require_once __DIR__ . "/common.php";
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            jsonResponse([
                "status" => "success",
                "data" => null
            ]);
        default:
            jsonResponse([
                "status" => "error",
                "message" => "Method not allowed."
            ], 405);
    }
} catch (Exception $e) {
    jsonResponse([
        "status" => "error",
        "message" => $e->getMessage()
    ], 500);
}