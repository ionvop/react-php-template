<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

function validateTaskAccess(SQLite3 $db, int $projectId, array $user): void {
    $project = executePreparedQuery($db, <<<SQL
        SELECT * FROM `projects` WHERE `project_id` = :project_id
    SQL, [
        ":project_id" => $projectId
    ])->fetchArray(SQLITE3_ASSOC);

    if ($project == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "project" => "Project not found."
            ]
        ], 404);
    }

    if ($project["user_id"] != $user["id"]) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "task" => "Task not found."
            ]
        ], 404);
    }
}

function getTaskById(SQLite3 $db, int $id, array $user): array {
    $task = executePreparedQuery($db, <<<SQL
        SELECT * FROM `tasks` WHERE `id` = :id
    SQL, [
        ":id" => $id
    ])->fetchArray(SQLITE3_ASSOC);

    if ($task == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "task" => "Task not found."
            ]
        ], 404);
    }

    validateTaskAccess($db, $task["project_id"], $user);
    return $task;
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
                $task = getTaskById($db, $_GET["id"], $user);
            
                jsonResponse([
                    "status" => "success",
                    "data" => [
                        "task" => $task
                    ]
                ]);
            }

            jsonResponse([
                "status" => "fail",
                "data" => [
                    "id" => "Task ID is required."
                ]
            ], 400);
        case "POST":
            switch ($data["_method"]) {
                case "PUT":
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "Task ID is required for update."
                            ]
                        ], 400);
                    }

                    $task = getTaskById($db, $_GET["id"], $user);

                    executePreparedQuery($db, <<<SQL
                        UPDATE `tasks`
                        SET `content` = :content
                        WHERE `id` = :id
                    SQL, [
                        ":id" => $task["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to update task."
                        ], 500);
                    });

                    $task = getTaskById($db, $task["id"], $user);
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => [
                            "task" => $task
                        ]
                    ]);
                case "DELETE":
                    if (isset($_GET["id"]) == false) {
                        jsonResponse([
                            "status" => "fail",
                            "data" => [
                                "id" => "Task ID is required for deletion."
                            ]
                        ], 400);
                    }

                    $task = getTaskById($db, $_GET["id"], $user);

                    executePreparedQuery($db, <<<SQL
                        DELETE FROM `tasks` WHERE `id` = :id
                    SQL, [
                        ":id" => $task["id"]
                    ], function() {
                        jsonResponse([
                            "status" => "error",
                            "message" => "Failed to delete task."
                        ], 500);
                    });
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ], 204);
            }

            validateTaskAccess($db, $data["project_id"], $user);

            executePreparedQuery($db, <<<SQL
                INSERT INTO `tasks` (`project_id`, `content`)
                VALUE (:project_id, :content)
            SQL, [
                ":project_id" => $data["project_id"],
                ":content" => $data["content"]
            ], function() {
                jsonResponse([
                    "status" => "error",
                    "message" => "Failed to create task."
                ], 500);
            });

            $task = getTaskById($db, $db->lastInsertRowID(), $user);
            
            jsonResponse([
                "status" => "success",
                "data" => [
                    "task" => $task
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