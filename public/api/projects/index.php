<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

function getProjectById(SQLite3 $db, int $id, array $user): array {
    $project = executePreparedQuery($db, <<<SQL
        SELECT * FROM `projects` WHERE `user_id` = :user_id AND `id` = :id
    SQL, [
        ":user_id" => $user["id"],
        ":id" => $id
    ])->fetchArray(SQLITE3_ASSOC);

    if ($project == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "project" => "Project not found."
            ]
        ], 404);
    }

    return $project;
}

try {
    $user = getUser($db);

    if ($user == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "session" => "Invalid session."
            ]
        ], 401);
    }

    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if (isset($_GET["id"])) {
                $project = getProjectById($db, $_GET["id"], $user);

                $taskResult = executePreparedQuery($db, <<<SQL
                    SELECT * FROM `tasks` WHERE `project_id` = :project_id
                SQL, [
                    ":project_id" => $project["id"]
                ]);

                $tasks = [];

                while ($task = $taskResult->fetchArray(SQLITE3_ASSOC)) {
                    $tasks[] = $task;
                }

                $project["tasks"] = $tasks;

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
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "Project ID is required for update."
                            ]
                        ], 400);
                    }

                    $project = getProjectById($db, $_GET["id"], $user);

                    executePreparedQuery($db, <<<SQL
                        UPDATE `projects`
                        SET `title` = :title, `description` = :description
                        WHERE `id` = :id
                    SQL, [
                        ":title" => $data["title"],
                        ":description" => $data["description"],
                        ":id" => $project["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to update project."
                        ], 500);
                    });

                    $project = getProjectById($db, $project["id"], $user);

                    jsonResponse([
                        "status" => "success",
                        "data" => [
                            "project" => $project
                        ]
                    ]);
                case "DELETE":
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "Project ID is required for deletion."
                            ]
                        ], 400);
                    }

                    $project = getProjectById($db, $_GET["id"], $user);

                    executePreparedQuery($db, <<<SQL
                        DELETE FROM `projects` WHERE `id` = :id
                    SQL, [
                        ":id" => $project["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to delete project."
                        ], 500);
                    });
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ], 204);
            }

            executePreparedQuery($db, <<<SQL
                INSERT INTO `projects` (`user_id`, `title`, `description`)
                VALUES (:user_id, :title, :description)
            SQL, [
                ":user_id" => $user["id"],
                ":title" => $data["title"],
                ":description" => $data["description"]
            ], function() {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to create project."
                ], 500);
            });

            $project = getProjectById($db, $db->lastInsertRowID(), $user);

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