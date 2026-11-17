<?php

require_once __DIR__ . "/common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            http_response_code(200);

            echo json_encode([
                "status" => "success",
                "data" => null
            ]);

            exit;
        default:
            http_response_code(405);

            echo json_encode([
                "status" => "error",
                "message" => "Method not allowed."
            ]);

            exit;
    }
} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}