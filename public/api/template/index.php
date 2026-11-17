<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["id"])) {
                // GET by ID logic goes here
                exit;
            }

            // GET all logic goes here
            exit;
        case "POST":
            switch ($data["_method"]) {
                case "PUT":
                    // PUT logic goes here
                    exit;
                case "DELETE":
                    // DELETE logic goes here
                    exit;
            }

            // POST logic goes here
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