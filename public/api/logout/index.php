<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            $user = getUser($db);

            if ($user == false) {
                http_response_code(401);

                echo json_encode([
                    "status" => "fail",
                    "data" => [
                        "session" => "Invalid session."
                    ]
                ]);

                exit;
            }

            executePreparedQuery($db, <<<SQL
                UPDATE `users` SET `session` = NULL WHERE `id` = :id
            SQL, [
                ":id" => $user["id"]
            ]);

            setcookie("session", "", time() - 3600, "/");
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