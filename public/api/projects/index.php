<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);
$user = getUser($db);

if ($user == false) {
    jsonResponse([
        "status" => "fail",
        "data" => [
            "session" => "Invalid session."
        ]
    ], 401);
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
                    jsonResponse([
                        "status" => "fail",
                        "data" => [
                            "project" => "Project not found."
                        ]
                    ], 404);
                }

                jsonResponse([
                    "status" => "success",
                    "data" => [
                        "project" => $project
                    ]
                ]);
            }

            $projectResult = executePreparedQuery($db, <<<SQL
                SELECT * FROM `projects` WHERE `user_id` = :user_id
            SQL, [
                ":user_id" => $user["id"]
            ]);

            if ($projectResult == false) {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to retrieve projects."
                ], 500);
            }

            $projects = [];

            while ($project = $projectResult->fetchArray(SQLITE3_ASSOC)) {
                $projects[] = $project;
            }

            jsonResponse([
                "status" => "success",
                "data" => [
                    "projects" => $projects
                ]
            ]);
        case "POST":
            switch ($data["_method"]) {
                case "PUT":
                    $success = executePreparedQuery($db, <<<SQL
                        UPDATE `projects`
                        SET `title` = :title, `description` = :description
                        WHERE `id` = :id
                    SQL, [
                        ":title" => $data["title"],
                        ":description" => $data["description"]
                    ]);

                    if ($success == false) {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to update project."
                        ], 500);
                    }

                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ]);
                case "DELETE":
                    // DELETE logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ]);
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
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to create project."
                ], 500);
            }

            $project = executePreparedQuery($db, <<<SQL
                SELECT * FROM `projects` WHERE `id` = :id
            SQL, [
                ":id" => $db->lastInsertRowID()
            ])->fetchArray(SQLITE3_ASSOC);

            if ($project == false) {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to retrieve project."
                ], 500);
            }

            jsonResponse([
                "status" => "success",
                "data" => [
                    "project" => $project
                ]
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