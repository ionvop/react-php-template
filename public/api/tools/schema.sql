CREATE TABLE `projects` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `user_id` INTEGER REFERENCES `users`(`id`), `title` TEXT, `description` TEXT, `created_at` TEXT DEFAULT (datetime()));
CREATE TABLE `tasks` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `project_id` INTEGER REFERENCES `projects`(`id`), `content` TEXT, `created_at` TEXT DEFAULT (datetime()));
CREATE TABLE "users" (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `username` TEXT UNIQUE, `hash` TEXT, `created_at` TEXT DEFAULT (datetime()));
