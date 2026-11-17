<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["id"])) {
                // GET by ID logic goes here
            
                jsonResponse([
                    "status" => "success",
                    "data" => null
                ]);
            }

            // GET all logic goes here
            
            jsonResponse([
                "status" => "success",
                "data" => null
            ]);
        case "POST":
            switch ($data["_method"]) {
                case "PUT":
                    // PUT logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ]);
                case "DELETE":
                    // DELETE logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ]);
            }

            // POST logic goes here
            
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