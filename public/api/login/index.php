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
                http_response_code(401);

                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "username" => "Invalid username or password."
                    ]
                ]);

                exit;
            }

            $success = password_verify($data["password"], $user["hash"]);

            if ($success == false) {
                http_response_code(401);

                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "username" => "Invalid username or password."
                    ]
                ]);

                exit;
            }

            $session = uniqid("session-");

            executePreparedQuery($db, <<<SQL
                UPDATE `users` SET `session` = :session WHERE `id` = :id
            SQL, [
                ":session" => $session,
                ":id" => $user["id"]
            ]);

            setcookie("session", $session, 86400, "/");
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