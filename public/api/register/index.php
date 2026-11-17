<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            if (trim($data["username"]) == "") {
                http_response_code(400);

                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "username" => "Username is required."
                    ]
                ]);

                exit;
            }

            if (strlen($data["password"]) < 6) {
                http_response_code(400);

                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "password" => "Password must be at least 6 characters long."
                    ]
                ]);

                exit;
            }

            $user = executePreparedQuery($db, <<<SQL
                SELECT * FROM `users` WHERE `username` = :username
            SQL, [
                ":username" => $data["username"]
            ])->fetchArray(SQLITE3_ASSOC);

            if ($user != false) {
                http_response_code(409);
                
                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "username" => "Username already exists."
                    ]
                ]);

                exit;
            }

            $hash = password_hash($data["password"], PASSWORD_DEFAULT);

            $success = executePreparedQuery($db, <<<SQL
                INSERT INTO `users` (`username`, `hash`)
                VALUES (:username, :hash)
            SQL, [
                ":username" => $data["username"]
            ]);

            if ($success == false) {
                http_response_code(500);

                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to create user."
                ]);

                exit;
            }

            http_response_code(201);

            echo json_encode([
                "status" => "success",
                "data" => null
            ]);

            exit;
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