<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
require_admin_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
if (!csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session expired. Reload the page and try again.']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload) || !is_array($payload['quiz'] ?? null) || !is_array($payload['questions'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'The quiz data could not be read.']);
    exit;
}

$quizData = $payload['quiz'];
$title = trim((string) ($quizData['title'] ?? ''));
$description = trim((string) ($quizData['description'] ?? ''));
$visibility = (string) ($quizData['visibility'] ?? 'private');
$status = (string) ($quizData['status'] ?? 'draft');
$defaultTimer = filter_var($quizData['default_timer'] ?? null, FILTER_VALIDATE_INT);
$basePoints = filter_var($quizData['base_points'] ?? null, FILTER_VALIDATE_INT);
$questions = $payload['questions'];

$error = null;
if ($title === '' || mb_strlen($title) > 120) {
    $error = 'Enter a quiz title of 1–120 characters.';
} elseif (mb_strlen($description) > 2000) {
    $error = 'The description must be 2,000 characters or fewer.';
} elseif (!in_array($visibility, ['private', 'public'], true) || !in_array($status, ['draft', 'published'], true)) {
    $error = 'Choose a valid visibility and status.';
} elseif ($defaultTimer === false || $defaultTimer < 0 || $defaultTimer > 300 || ($defaultTimer > 0 && $defaultTimer < 5)) {
    $error = 'The default timer must be 0 (off) or between 5 and 300 seconds.';
} elseif ($basePoints === false || $basePoints < 0 || $basePoints > 100000) {
    $error = 'Base points must be between 0 and 100,000.';
} elseif (count($questions) > 100) {
    $error = 'A quiz can contain up to 100 questions.';
}

$normalizedQuestions = [];
if ($error === null) {
    foreach ($questions as $index => $question) {
        if (!is_array($question)) {
            $error = 'One of the questions has invalid data.';
            break;
        }
        $text = trim((string) ($question['question_text'] ?? ''));
        $type = (string) ($question['question_type'] ?? '');
        $timer = filter_var($question['timer_seconds'] ?? 0, FILTER_VALIDATE_INT);
        $points = filter_var($question['points'] ?? null, FILTER_VALIDATE_INT);
        $explanation = trim((string) ($question['explanation'] ?? ''));
        if ($text === '' || mb_strlen($text) > 1000) {
            $error = 'Every question needs text of 1–1,000 characters.';
            break;
        }
        if (!in_array($type, ['multiple_choice', 'true_false', 'fill_blank'], true)) {
            $error = 'Choose a supported question type.';
            break;
        }
        if ($timer === false || $timer < 0 || $timer > 300 || ($timer > 0 && $timer < 5)) {
            $error = 'Question timers must be 0 (default) or between 5 and 300 seconds.';
            break;
        }
        if ($points === false || $points < 0 || $points > 100000 || mb_strlen($explanation) > 2000) {
            $error = 'Check the points and explanation for each question.';
            break;
        }

        $options = null;
        $acceptedAnswers = null;
        $answerKey = null;
        $caseSensitive = !empty($question['case_sensitive']) ? 1 : 0;
        if ($type === 'multiple_choice') {
            $rawOptions = $question['options'] ?? null;
            if (!is_array($rawOptions) || count($rawOptions) < 2 || count($rawOptions) > 4) {
                $error = 'Multiple-choice questions need 2–4 answer choices.';
                break;
            }
            $options = [];
            $correctCount = 0;
            foreach ($rawOptions as $option) {
                $optionText = is_array($option) ? trim((string) ($option['text'] ?? '')) : '';
                if ($optionText === '' || mb_strlen($optionText) > 250) {
                    $error = 'Each answer choice must contain 1–250 characters.';
                    break 2;
                }
                $isCorrect = !empty($option['correct']);
                $correctCount += $isCorrect ? 1 : 0;
                $options[] = ['text' => $optionText, 'correct' => $isCorrect];
            }
            if ($correctCount !== 1) {
                $error = 'Choose exactly one correct answer for each multiple-choice question.';
                break;
            }
        } elseif ($type === 'true_false') {
            $answerKey = (string) ($question['answer'] ?? '');
            if (!in_array($answerKey, ['true', 'false'], true)) {
                $error = 'Choose whether the true/false statement is true or false.';
                break;
            }
        } else {
            $rawAnswers = $question['accepted_answers'] ?? null;
            if (!is_array($rawAnswers)) {
                $error = 'Add at least one accepted fill-in-the-blank answer.';
                break;
            }
            $acceptedAnswers = [];
            foreach ($rawAnswers as $acceptedAnswer) {
                $acceptedAnswer = trim((string) $acceptedAnswer);
                if ($acceptedAnswer !== '') {
                    if (mb_strlen($acceptedAnswer) > 250) {
                        $error = 'Accepted answers must be 250 characters or fewer.';
                        break 2;
                    }
                    $acceptedAnswers[] = $acceptedAnswer;
                }
            }
            $acceptedAnswers = array_values(array_unique($acceptedAnswers));
            if (!$acceptedAnswers) {
                $error = 'Add at least one accepted fill-in-the-blank answer.';
                break;
            }
        }

        $normalizedQuestions[] = [
            'question_text' => $text,
            'question_type' => $type,
            'options_json' => $options === null ? null : json_encode($options, JSON_THROW_ON_ERROR),
            'accepted_answers_json' => $acceptedAnswers === null ? null : json_encode($acceptedAnswers, JSON_THROW_ON_ERROR),
            'answer_key' => $answerKey,
            'case_sensitive' => $caseSensitive,
            'explanation' => $explanation,
            'timer_seconds' => $timer === 0 ? null : $timer,
            'points' => $points,
            'position' => $index + 1,
        ];
    }
}

if ($error !== null) {
    http_response_code(422);
    echo json_encode(['error' => $error]);
    exit;
}

$quizId = isset($quizData['id']) && $quizData['id'] !== '' ? filter_var($quizData['id'], FILTER_VALIDATE_INT) : null;
if ($quizId === false || ($quizId !== null && $quizId < 1)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid quiz.']);
    exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    if ($quizId !== null) {
        $check = $pdo->prepare('SELECT id FROM quizzes WHERE id = :id AND admin_id = :admin_id FOR UPDATE');
        $check->execute(['id' => $quizId, 'admin_id' => $_SESSION['admin_id']]);
        if (!$check->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Quiz not found.']);
            exit;
        }
        $statement = $pdo->prepare(
            'UPDATE quizzes SET title = :title, description = :description, visibility = :visibility,
             status = :status, default_timer = :default_timer, base_points = :base_points,
             randomize_questions = :randomize_questions, randomize_answers = :randomize_answers,
             show_leaderboard = :show_leaderboard WHERE id = :id AND admin_id = :admin_id'
        );
        $statement->execute([
            'title' => $title,
            'description' => $description,
            'visibility' => $visibility,
            'status' => $status,
            'default_timer' => $defaultTimer,
            'base_points' => $basePoints,
            'randomize_questions' => !empty($quizData['randomize_questions']) ? 1 : 0,
            'randomize_answers' => !empty($quizData['randomize_answers']) ? 1 : 0,
            'show_leaderboard' => !empty($quizData['show_leaderboard']) ? 1 : 0,
            'id' => $quizId,
            'admin_id' => $_SESSION['admin_id'],
        ]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO quizzes
             (admin_id, title, description, visibility, status, default_timer, base_points,
              randomize_questions, randomize_answers, show_leaderboard)
             VALUES
             (:admin_id, :title, :description, :visibility, :status, :default_timer, :base_points,
              :randomize_questions, :randomize_answers, :show_leaderboard)'
        );
        $statement->execute([
            'admin_id' => $_SESSION['admin_id'],
            'title' => $title,
            'description' => $description,
            'visibility' => $visibility,
            'status' => $status,
            'default_timer' => $defaultTimer,
            'base_points' => $basePoints,
            'randomize_questions' => !empty($quizData['randomize_questions']) ? 1 : 0,
            'randomize_answers' => !empty($quizData['randomize_answers']) ? 1 : 0,
            'show_leaderboard' => !empty($quizData['show_leaderboard']) ? 1 : 0,
        ]);
        $quizId = (int) $pdo->lastInsertId();
    }

    $delete = $pdo->prepare('DELETE FROM quiz_questions WHERE quiz_id = :quiz_id');
    $delete->execute(['quiz_id' => $quizId]);
    $insert = $pdo->prepare(
        'INSERT INTO quiz_questions
         (quiz_id, question_text, question_type, options_json, accepted_answers_json, answer_key,
          case_sensitive, explanation, timer_seconds, points, position)
         VALUES
         (:quiz_id, :question_text, :question_type, :options_json, :accepted_answers_json, :answer_key,
          :case_sensitive, :explanation, :timer_seconds, :points, :position)'
    );
    foreach ($normalizedQuestions as $question) {
        $insert->execute(['quiz_id' => $quizId] + $question);
    }
    $pdo->commit();
    echo json_encode(['id' => $quizId, 'message' => 'Quiz saved.']);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('BootUp save quiz failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'The quiz could not be saved due to a database error.']);
}
