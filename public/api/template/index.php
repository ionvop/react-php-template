<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

// Endpoint-local helper functions go here

try {
    // Endpoint authentication and authorization logic goes here

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
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "ID is required for update."
                            ]
                        ], 400);
                    }

                    // PUT logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ]);
                case "DELETE":
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "ID is required for deletion."
                            ]
                        ], 400);
                    }

                    // DELETE logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ], 204);
            }

            // POST logic goes here
            
            jsonResponse([
                "status" => "success",
                "data" => null
            ], 201);
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