<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            if (trim($data["username"]) == "") {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "username" => "Username is required."
                    ]
                ], 400);
            }

            if (strlen($data["password"]) < 6) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "password" => "Password must be at least 6 characters long."
                    ]
                ], 400);
            }

            $user = executePreparedQuery($db, <<<SQL
                SELECT * FROM `users` WHERE `username` = :username
            SQL, [
                ":username" => $data["username"]
            ])->fetchArray(SQLITE3_ASSOC);

            if ($user != false) {
                jsonResponse([
                    "status" => "fail",
                    "data" => [
                        "username" => "Username already exists."
                    ]
                ], 409);
            }

            $hash = password_hash($data["password"], PASSWORD_DEFAULT);

            executePreparedQuery($db, <<<SQL
                INSERT INTO `users` (`username`, `hash`)
                VALUES (:username, :hash)
            SQL, [
                ":username" => $data["username"]
            ], function() {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to create user."
                ], 500);
            });

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