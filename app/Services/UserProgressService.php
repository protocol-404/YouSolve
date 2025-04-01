<?php

namespace App\Services;

use App\Models\Problem;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserProgressService
{
    /**
     * Get progress statistics for a user
     *
     * @param User $user
     * @return array
     */
    public function getUserProgress(User $user)
    {
        try {
            // Get all submissions for this user
            $submissions = Submission::where('user_id', $user->id)
                ->with(['result', 'problem'])
                ->get();
            
            // Calculate statistics
            $totalSubmissions = $submissions->count();
            $successfulSubmissions = $submissions->filter(function ($submission) {
                return $submission->result && $submission->result->success;
            })->count();
            
            // Group submissions by language
            $submissionsByLanguage = $submissions->groupBy(function ($submission) {
                return $submission->language;
            })->map(function ($items) {
                return $items->count();
            })->toArray();
            
            // Group submissions by problem difficulty
            $submissionsByDifficulty = $submissions->groupBy(function ($submission) {
                return $submission->problem ? $submission->problem->difficulty : 'unknown';
            })->map(function ($items) {
                return $items->count();
            })->toArray();
            
            // Get solved problems
            $solvedProblemIds = $submissions->filter(function ($submission) {
                return $submission->result && $submission->result->success;
            })->pluck('problem_id')->unique()->toArray();
            
            // Get recent submissions
            $recentSubmissions = $submissions->sortByDesc('created_at')->take(5)->values();
            
            return [
                'user_id' => $user->id,
                'total_submissions' => $totalSubmissions,
                'successful_submissions' => $successfulSubmissions,
                'success_rate' => $totalSubmissions > 0 ? ($successfulSubmissions / $totalSubmissions) * 100 : 0,
                'submissions_by_language' => $submissionsByLanguage,
                'submissions_by_difficulty' => $submissionsByDifficulty,
                'solved_problems_count' => count($solvedProblemIds),
                'recent_submissions' => $recentSubmissions
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user progress: ' . $e->getMessage());
            return [
                'user_id' => $user->id,
                'error' => 'Failed to retrieve progress data'
            ];
        }
    }
    
    /**
     * Get progress for a specific evaluation
     *
     * @param User $user
     * @param int $evaluationId
     * @return array
     */
    public function getEvaluationProgress(User $user, $evaluationId)
    {
        try {
            // Get problems for this evaluation
            $problems = Problem::where('evaluation_id', $evaluationId)->get();
            $problemIds = $problems->pluck('id')->toArray();
            $totalProblems = count($problemIds);
            
            if ($totalProblems === 0) {
                return [
                    'user_id' => $user->id,
                    'evaluation_id' => $evaluationId,
                    'total_problems' => 0,
                    'solved_problems' => 0,
                    'completion_percentage' => 0,
                    'problems' => []
                ];
            }
            
            // Get submissions for these problems
            $submissions = Submission::where('user_id', $user->id)
                ->whereIn('problem_id', $problemIds)
                ->with(['result', 'problem'])
                ->get();
            
            // Group submissions by problem
            $submissionsByProblem = $submissions->groupBy('problem_id');
            
            // Calculate problem-specific progress
            $problemsProgress = [];
            $solvedProblems = 0;
            
            foreach ($problems as $problem) {
                $problemSubmissions = $submissionsByProblem->get($problem->id, collect([]));
                $bestSubmission = $problemSubmissions->sortByDesc(function ($submission) {
                    return $submission->result ? $submission->result->score : 0;
                })->first();
                
                $isSolved = false;
                $bestScore = 0;
                
                if ($bestSubmission && $bestSubmission->result && $bestSubmission->result->success) {
                    $isSolved = true;
                    $bestScore = $bestSubmission->result->score;
                    $solvedProblems++;
                }
                
                $problemsProgress[] = [
                    'problem_id' => $problem->id,
                    'title' => $problem->title,
                    'difficulty' => $problem->difficulty,
                    'is_solved' => $isSolved,
                    'attempts' => $problemSubmissions->count(),
                    'best_score' => $bestScore
                ];
            }
            
            // Calculate completion percentage
            $completionPercentage = $totalProblems > 0 ? ($solvedProblems / $totalProblems) * 100 : 0;
            
            return [
                'user_id' => $user->id,
                'evaluation_id' => $evaluationId,
                'total_problems' => $totalProblems,
                'solved_problems' => $solvedProblems,
                'completion_percentage' => $completionPercentage,
                'problems' => $problemsProgress
            ];
        } catch (\Exception $e) {
            Log::error('Error getting evaluation progress: ' . $e->getMessage());
            return [
                'user_id' => $user->id,
                'evaluation_id' => $evaluationId,
                'error' => 'Failed to retrieve evaluation progress data'
            ];
        }
    }
    
    /**
     * Get learning path recommendations for a user
     *
     * @param User $user
     * @return array
     */
    public function getLearningPathRecommendations(User $user)
    {
        try {
            // Get user's solved problems
            $solvedProblemIds = Submission::where('user_id', $user->id)
                ->whereHas('result', function ($query) {
                    $query->where('success', true);
                })
                ->pluck('problem_id')
                ->unique()
                ->toArray();
            
            // Get user's attempted but unsolved problems
            $attemptedProblemIds = Submission::where('user_id', $user->id)
                ->pluck('problem_id')
                ->unique()
                ->toArray();
            
            $unsolvedProblemIds = array_diff($attemptedProblemIds, $solvedProblemIds);
            
            // Get problems by difficulty
            $easyProblems = Problem::where('difficulty', 'easy')
                ->whereNotIn('id', $solvedProblemIds)
                ->limit(3)
                ->get();
            
            $mediumProblems = Problem::where('difficulty', 'medium')
                ->whereNotIn('id', $solvedProblemIds)
                ->limit(3)
                ->get();
            
            $hardProblems = Problem::where('difficulty', 'hard')
                ->whereNotIn('id', $solvedProblemIds)
                ->limit(3)
                ->get();
            
            // Prioritize unsolved problems that were attempted
            $priorityProblems = Problem::whereIn('id', $unsolvedProblemIds)
                ->limit(3)
                ->get();
            
            // Get problems by language (based on user's most used language)
            $mostUsedLanguage = Submission::where('user_id', $user->id)
                ->select('language', DB::raw('count(*) as count'))
                ->groupBy('language')
                ->orderBy('count', 'desc')
                ->first();
            
            $languageProblems = [];
            if ($mostUsedLanguage) {
                $languageProblems = Problem::whereHas('evaluation', function ($query) use ($mostUsedLanguage) {
                    $query->where('language', $mostUsedLanguage->language);
                })
                ->whereNotIn('id', $solvedProblemIds)
                ->limit(3)
                ->get();
            }
            
            return [
                'user_id' => $user->id,
                'priority_problems' => $priorityProblems,
                'recommended_by_difficulty' => [
                    'easy' => $easyProblems,
                    'medium' => $mediumProblems,
                    'hard' => $hardProblems
                ],
                'recommended_by_language' => $languageProblems,
                'most_used_language' => $mostUsedLanguage ? $mostUsedLanguage->language : null
            ];
        } catch (\Exception $e) {
            Log::error('Error getting learning path recommendations: ' . $e->getMessage());
            return [
                'user_id' => $user->id,
                'error' => 'Failed to retrieve learning path recommendations'
            ];
        }
    }
    
    /**
     * Get user activity timeline
     *
     * @param User $user
     * @param int $days
     * @return array
     */
    public function getUserActivityTimeline(User $user, $days = 30)
    {
        try {
            // Get submissions for the last X days
            $startDate = now()->subDays($days);
            $submissions = Submission::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->with('result')
                ->orderBy('created_at')
                ->get();
            
            // Group submissions by day
            $submissionsByDay = $submissions->groupBy(function ($submission) {
                return $submission->created_at->format('Y-m-d');
            });
            
            // Generate timeline data
            $timeline = [];
            $currentDate = $startDate->copy();
            $endDate = now();
            
            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $daySubmissions = $submissionsByDay->get($dateString, collect([]));
                
                $successfulSubmissions = $daySubmissions->filter(function ($submission) {
                    return $submission->result && $submission->result->success;
                })->count();
                
                $timeline[] = [
                    'date' => $dateString,
                    'total_submissions' => $daySubmissions->count(),
                    'successful_submissions' => $successfulSubmissions
                ];
                
                $currentDate->addDay();
            }
            
            return [
                'user_id' => $user->id,
                'days' => $days,
                'timeline' => $timeline
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user activity timeline: ' . $e->getMessage());
            return [
                'user_id' => $user->id,
                'error' => 'Failed to retrieve activity timeline'
            ];
        }
    }
}
