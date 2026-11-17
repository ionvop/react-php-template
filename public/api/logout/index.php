<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            $user = getUser($db);

            if ($user == false) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "session" => "Invalid session."
                    ]
                ], 401);
            }

            executePreparedQuery($db, <<<SQL
                UPDATE `users` SET `session` = NULL WHERE `id` = :id
            SQL, [
                ":id" => $user["id"]
            ], function() {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to delete session."
                ], 500);
            });

            setcookie("session", "", time() - 3600, "/");

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