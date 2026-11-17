<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            $user = executePreparedQuery($db, <<<SQL
                SELECT * FROM `users` WHERE `username` = :username
            SQL, [
                ":username" => $data["username"]
            ])->fetchArray(SQLITE3_ASSOC);

            if ($user == false) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "username" => "Invalid username or password."
                    ]
                ], 401);
            }

            $success = password_verify($data["password"], $user["hash"]);

            if ($success == false) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "username" => "Invalid username or password."
                    ]
                ], 401);
            }

            $session = uniqid("session-");

            $success = executePreparedQuery($db, <<<SQL
                UPDATE `users` SET `session` = :session WHERE `id` = :id
            SQL, [
                ":session" => $session,
                ":id" => $user["id"]
            ]);

            if ($success == false) {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to create session."
                ], 500);
            }

            setcookie("session", $session, 86400, "/");

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