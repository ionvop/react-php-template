<?php

require_once __DIR__ . "/../common.php";
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);
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

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["id"])) {
                $project = executePreparedQuery($db, <<<SQL
                    SELECT * FROM `projects` WHERE `user_id` = :user_id AND `id` = :id
                SQL, [
                    ":user_id" => $user["id"],
                    ":id" => $_GET["id"]
                ])->fetchArray(SQLITE3_ASSOC);

                if ($project == false) {
                    http_response_code(404);

                    echo json_encode([
                        "status" => "fail",
                        "data" => [
                            "project" => "Project not found."
                        ]
                    ]);

                    exit;
                }

                exit;
            }

            $projectResult = executePreparedQuery($db, <<<SQL
                SELECT * FROM `projects` WHERE `user_id` = :user_id
            SQL, [
                ":user_id" => $user["id"]
            ]);

            $projects = [];

            while ($project = $projectResult->fetchArray(SQLITE3_ASSOC)) {
                $projects[] = $project;
            }

            http_response_code(200);

            echo json_encode([
                "status" => "success",
                "data" => [
                    "projects" => $projects
                ]
            ]);

            exit;
        case "POST":
            switch ($data["_method"]) {
                case "PUT":
                    // PUT logic goes here
                    exit;
                case "DELETE":
                    // DELETE logic goes here
                    exit;
            }

            $success = executePreparedQuery($db, <<<SQL
                INSERT INTO `projects` (`user_id`, `title`, `description`)
                VALUES (:user_id, :title, :description)
            SQL, [
                ":user_id" => $user["id"],
                ":title" => $data["title"],
                ":description" => $data["description"]
            ]);

            if ($success == false) {
                http_response_code(500);

                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to create project."
                ]);

                exit;
            }

            $project = executePreparedQuery($db, <<<SQL
                SELECT * FROM `projects` WHERE `id` = :id
            SQL, [
                ":id" => $db->lastInsertRowID()
            ])->fetchArray(SQLITE3_ASSOC);

            if ($project == false) {
                http_response_code(500);

                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to retrieve project."
                ]);

                exit;
            }

            http_response_code(201);

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