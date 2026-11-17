<?php

require_once __DIR__ . "/../common.php";

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            $user = getUser($db);

            if ($user == false) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "session" => "Invalid session."
                    ]
                ], 401);
            }

            jsonResponse([
                "status" => "success",
                "data" => [
                    "user" => [
                        "id" => (int) $user["id"],
                        "username" => $user["username"],
                        "created_at" => $user["created_at"]
                    ]
                ]
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