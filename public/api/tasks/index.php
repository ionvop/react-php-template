<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

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
                $task = executePreparedQuery($db, <<<SQL
                    SELECT * FROM `tasks` WHERE `id` = :id
                SQL, [
                    ":id" => $_GET["id"]
                ])->fetchArray(SQLITE3_ASSOC);

                if ($task == false) {
                    jsonResponse([
                        "status" => "fail",
                        "data" => [
                            "task" => "Task not found."
                        ]
                    ], 404);
                }

                $project = executePreparedQuery($db, <<<SQL
                    SELECT * FROM `projects` WHERE `project_id` = :project_id
                SQL, [
                    ":project_id" => $task["project_id"]
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
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
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

                    // DELETE logic goes here
                    
                    jsonResponse([
                        "status" => "success",
                        "data" => null
                    ], 204);
            }

            // POST logic goes here
            
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