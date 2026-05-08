<?php
namespace Managers;

use Core\JsonStore;

class QuizManager {
    private $questionStore;
    private $resultStore;

    public function __construct() {
        $this->questionStore = new JsonStore('quiz_questions');
        $this->resultStore = new JsonStore('quiz_results');
    }

    public function getQuestions($limit = 10) {
        $questions = $this->questionStore->findAll();
        shuffle($questions);
        return array_slice($questions, 0, $limit);
    }

    public function submitQuiz($userId, $userName, $answers) {
        $questions = $this->questionStore->findAll();
        $totalQuestions = count($answers);
        $correctCount = 0;
        $details = [];

        foreach ($answers as $qId => $selectedIndex) {
            $question = null;
            foreach ($questions as $q) {
                if ($q['id'] === $qId) {
                    $question = $q;
                    break;
                }
            }

            if ($question) {
                $isCorrect = ($question['correct_index'] == $selectedIndex);
                if ($isCorrect) $correctCount++;

                $details[] = [
                    'question_id' => $qId,
                    'question_text' => $question['question'],
                    'selected_index' => $selectedIndex,
                    'correct_index' => $question['correct_index'],
                    'is_correct' => $isCorrect
                ];
            }
        }

        $score = ($totalQuestions > 0) ? round(($correctCount / $totalQuestions) * 100) : 0;

        $result = [
            'user_id' => $userId,
            'user_name' => $userName,
            'timestamp' => date('Y-m-d H:i:s'),
            'score' => $score,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'passed' => ($score >= 80), // Passing grade 80%
            'details' => $details
        ];

        return $this->resultStore->create($result);
    }

    public function getResults($userId = null) {
        $results = $this->resultStore->findAll();
        if ($userId) {
            $results = array_filter($results, function($r) use ($userId) {
                return $r['user_id'] === $userId;
            });
            return array_values($results);
        }
        return $results;
    }
}
