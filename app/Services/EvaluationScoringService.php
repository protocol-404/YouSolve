<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Result;
use App\Models\Problem;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EvaluationScoringService
{
    /**
     * Calculate score for a submission result
     *
     * @param Result $result
     * @return int
     */
    public function calculateScore(Result $result)
    {
        // If the submission failed, score is 0
        if (!$result->success) {
            return 0;
        }
        
        $submission = Submission::find($result->submission_id);
        if (!$submission) {
            return 0;
        }
        
        $problem = Problem::find($submission->problem_id);
        if (!$problem) {
            return 0;
        }
        
        // Base score depends on difficulty
        $baseScore = $this->getBaseScoreForDifficulty($problem->difficulty);
        
        // Performance multiplier based on execution time and memory usage
        $performanceMultiplier = $this->calculatePerformanceMultiplier(
            $result->execution_time,
            $result->memory_usage,
            $problem->time_limit,
            $problem->memory_limit
        );
        
        // Calculate final score
        $score = round($baseScore * $performanceMultiplier);
        
        // Update the result with the score
        $result->score = $score;
        $result->save();
        
        return $score;
    }
    
    /**
     * Get base score for a difficulty level
     *
     * @param string $difficulty
     * @return int
     */
    protected function getBaseScoreForDifficulty($difficulty)
    {
        switch ($difficulty) {
            case 'easy':
                return 100;
            case 'medium':
                return 200;
            case 'hard':
                return 300;
            default:
                return 100;
        }
    }
    
    /**
     * Calculate performance multiplier based on execution metrics
     *
     * @param int $executionTime
     * @param int $memoryUsage
     * @param int $timeLimit
     * @param int $memoryLimit
     * @return float
     */
    protected function calculatePerformanceMultiplier($executionTime, $memoryUsage, $timeLimit, $memoryLimit)
    {
        // Default limits if not specified
        $timeLimit = $timeLimit ?: 1000;
        $memoryLimit = $memoryLimit ?: 128 * 1024;
        
        // Calculate time efficiency (0.5 to 1.0)
        $timeEfficiency = 0.5;
        if ($executionTime > 0 && $timeLimit > 0) {
            $timeRatio = $executionTime / $timeLimit;
            $timeEfficiency = 1.0 - min(0.5, $timeRatio * 0.5);
        }
        
        // Calculate memory efficiency (0.5 to 1.0)
        $memoryEfficiency = 0.5;
        if ($memoryUsage > 0 && $memoryLimit > 0) {
            $memoryRatio = $memoryUsage / $memoryLimit;
            $memoryEfficiency = 1.0 - min(0.5, $memoryRatio * 0.5);
        }
        
        // Combined multiplier (0.5 to 1.0)
        return ($timeEfficiency + $memoryEfficiency) / 2;
    }
    
    /**
     * Update user's total score
     *
     * @param User $user
     * @return int
     */
    public function updateUserTotalScore(User $user)
    {
        try {
            // Get all successful submissions for this user
            $submissions = Submission::where('user_id', $user->id)
                ->whereHas('result', function ($query) {
                    $query->where('success', true);
                })
                ->with('result')
                ->get();
            
            // Calculate total score
            $totalScore = 0;
            $problemsScored = [];
            
            foreach ($submissions as $submission) {
                if (!isset($submission->result)) {
                    continue;
                }
                
                // Only count the highest score for each problem
                if (!isset($problemsScored[$submission->problem_id]) || 
                    $submission->result->score > $problemsScored[$submission->problem_id]) {
                    $problemsScored[$submission->problem_id] = $submission->result->score;
                }
            }
            
            // Sum up the highest scores
            $totalScore = array_sum($problemsScored);
            
            // Update user's total score
            $user->total_score = $totalScore;
            $user->save();
            
            return $totalScore;
        } catch (\Exception $e) {
            Log::error('Error updating user total score: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user ranking based on total score
     *
     * @param User $user
     * @return int
     */
    public function getUserRanking(User $user)
    {
        try {
            // Count users with higher scores
            $higherScoreCount = User::where('total_score', '>', $user->total_score)->count();
            
            // Ranking is 1-based
            return $higherScoreCount + 1;
        } catch (\Exception $e) {
            Log::error('Error getting user ranking: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get top performers for an evaluation
     *
     * @param int $evaluationId
     * @param int $limit
     * @return array
     */
    public function getTopPerformers($evaluationId, $limit = 10)
    {
        try {
            // Get problems for this evaluation
            $problemIds = Problem::where('evaluation_id', $evaluationId)
                ->pluck('id')
                ->toArray();
            
            if (empty($problemIds)) {
                return [];
            }
            
            // Get users with submissions for these problems
            $users = User::whereHas('submissions', function ($query) use ($problemIds) {
                $query->whereIn('problem_id', $problemIds)
                    ->whereHas('result', function ($q) {
                        $q->where('success', true);
                    });
            })->get();
            
            // Calculate evaluation score for each user
            $userScores = [];
            
            foreach ($users as $user) {
                $evaluationScore = 0;
                $problemsScored = [];
                
                $submissions = Submission::where('user_id', $user->id)
                    ->whereIn('problem_id', $problemIds)
                    ->whereHas('result', function ($query) {
                        $query->where('success', true);
                    })
                    ->with('result')
                    ->get();
                
                foreach ($submissions as $submission) {
                    if (!isset($submission->result)) {
                        continue;
                    }
                    
                    // Only count the highest score for each problem
                    if (!isset($problemsScored[$submission->problem_id]) || 
                        $submission->result->score > $problemsScored[$submission->problem_id]) {
                        $problemsScored[$submission->problem_id] = $submission->result->score;
                    }
                }
                
                // Sum up the highest scores
                $evaluationScore = array_sum($problemsScored);
                
                $userScores[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'score' => $evaluationScore,
                    'problems_solved' => count($problemsScored),
                    'total_problems' => count($problemIds)
                ];
            }
            
            // Sort by score (descending)
            usort($userScores, function ($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            // Limit results
            return array_slice($userScores, 0, $limit);
        } catch (\Exception $e) {
            Log::error('Error getting top performers: ' . $e->getMessage());
            return [];
        }
    }
}
