CREATE DATABASE IF NOT EXISTS bootup
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE bootup;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(32) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quizzes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    visibility ENUM('private', 'public') NOT NULL DEFAULT 'private',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    default_timer SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    base_points INT UNSIGNED NOT NULL DEFAULT 1000,
    randomize_questions TINYINT(1) NOT NULL DEFAULT 0,
    randomize_answers TINYINT(1) NOT NULL DEFAULT 0,
    show_leaderboard TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quizzes_admin_updated (admin_id, updated_at),
    CONSTRAINT fk_quizzes_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_text VARCHAR(1000) NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank') NOT NULL,
    options_json JSON NULL,
    accepted_answers_json JSON NULL,
    answer_key VARCHAR(250) NULL,
    case_sensitive TINYINT(1) NOT NULL DEFAULT 0,
    explanation VARCHAR(2000) NOT NULL DEFAULT '',
    timer_seconds SMALLINT UNSIGNED NULL COMMENT 'NULL uses the quiz default',
    points INT UNSIGNED NOT NULL DEFAULT 1000,
    position SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_questions_quiz_position (quiz_id, position),
    CONSTRAINT fk_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS game_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    game_code CHAR(6) NOT NULL,
    status ENUM('lobby', 'started', 'ended') NOT NULL DEFAULT 'lobby',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_game_sessions_code (game_code),
    KEY idx_game_sessions_admin_status (admin_id, status),
    CONSTRAINT fk_game_sessions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE,
    CONSTRAINT fk_game_sessions_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS players (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    game_session_id BIGINT UNSIGNED NOT NULL,
    player_name VARCHAR(24) NOT NULL,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_players_session_name (game_session_id, player_name),
    KEY idx_players_session (game_session_id),
    CONSTRAINT fk_players_game_session FOREIGN KEY (game_session_id) REFERENCES game_sessions (id) ON DELETE CASCADE
) ENGINE=InnoDB;
