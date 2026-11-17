<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

function getUserById(SQLite3 $db, int $id): array {
    $user = executePreparedQuery($db, <<<SQL
        SELECT * FROM `users` WHERE `id` = :id
    SQL, [
        ":id" => $id
    ])->fetchArray(SQLITE3_ASSOC);

    if ($user == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "user" => "User not found."
            ]
        ], 404);
    }

    return $user;
}

try {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);

    if ($headers == false || isset($headers["authorization"]) == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "authorization" => "Authorization header is required."
            ]
        ], 400);
    }

    if ($headers["authorization"] != "Bearer " . $ADMIN_KEY) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "authorization" => "Invalid authorization key."
            ]
        ], 400);
    }

    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["id"])) {
                $user = getUserById($db, $_GET["id"]);
            
                jsonResponse([
                    "status" => "success",
                    "data" => $user
                ]);
            }

            $userResult = executePreparedQuery($db, <<<SQL
                SELECT * FROM `users`
            SQL);

            $users = [];

            while ($user = $userResult->fetchArray(SQLITE3_ASSOC)) {
                $users[] = $user;
            }
            
            jsonResponse([
                "status" => "success",
                "data" => [
                    "users" => $users
                ]
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

                    $user = getUserById($db, $_GET["id"]);

                    executePreparedQuery($db, <<<SQL
                        UPDATE `users`
                        SET `username` = :username
                        WHERE `id` = :id
                    SQL, [
                        ":id" => $user["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to update user."
                        ], 500);
                    });

                    $user = getUserById($db, $user["id"]);
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => [
                            "user" => $user
                        ]
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

                    $user = getUserById($db, $_GET["id"]);

                    executePreparedQuery($db, <<<SQL
                        DELETE FROM `users` WHERE `id` = :id
                    SQL, [
                        ":id" => $user["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to delete user."
                        ], 500);
                    });
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ], 204);
            }

            jsonResponse([
                "status" => "error",
                "message" => "Method not allowed."
            ], 405);
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