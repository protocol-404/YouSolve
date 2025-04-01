<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserProgressService;
use App\Services\EvaluationScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProgressController extends Controller
{
    protected $progressService;
    protected $scoringService;
    
    public function __construct(
        UserProgressService $progressService,
        EvaluationScoringService $scoringService
    ) {
        $this->progressService = $progressService;
        $this->scoringService = $scoringService;
    }
    
    /**
     * Get progress for the authenticated user
     */
    public function getMyProgress()
    {
        $user = Auth::user();
        $progress = $this->progressService->getUserProgress($user);
        
        return response()->json($progress);
    }
    
    /**
     * Get progress for a specific user (admin/instructor only)
     */
    public function getUserProgress($userId)
    {
        // Check if user is authorized
        if (!Auth::user()->hasRole('administrator') && !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        $progress = $this->progressService->getUserProgress($user);
        
        return response()->json($progress);
    }
    
    /**
     * Get evaluation progress for the authenticated user
     */
    public function getMyEvaluationProgress($evaluationId)
    {
        $user = Auth::user();
        $progress = $this->progressService->getEvaluationProgress($user, $evaluationId);
        
        return response()->json($progress);
    }
    
    /**
     * Get evaluation progress for a specific user (admin/instructor only)
     */
    public function getUserEvaluationProgress($userId, $evaluationId)
    {
        // Check if user is authorized
        if (!Auth::user()->hasRole('administrator') && !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        $progress = $this->progressService->getEvaluationProgress($user, $evaluationId);
        
        return response()->json($progress);
    }
    
    /**
     * Get learning path recommendations for the authenticated user
     */
    public function getMyLearningPathRecommendations()
    {
        $user = Auth::user();
        $recommendations = $this->progressService->getLearningPathRecommendations($user);
        
        return response()->json($recommendations);
    }
    
    /**
     * Get activity timeline for the authenticated user
     */
    public function getMyActivityTimeline(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 30);
        $timeline = $this->progressService->getUserActivityTimeline($user, $days);
        
        return response()->json($timeline);
    }
    
    /**
     * Get user score and ranking
     */
    public function getUserScore($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        // Update user's total score
        $totalScore = $this->scoringService->updateUserTotalScore($user);
        
        // Get user ranking
        $ranking = $this->scoringService->getUserRanking($user);
        
        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'total_score' => $totalScore,
            'ranking' => $ranking
        ]);
    }
    
    /**
     * Get evaluation leaderboard
     */
    public function getEvaluationLeaderboard($evaluationId, Request $request)
    {
        $limit = $request->input('limit', 10);
        $topPerformers = $this->scoringService->getTopPerformers($evaluationId, $limit);
        
        return response()->json([
            'evaluation_id' => $evaluationId,
            'top_performers' => $topPerformers
        ]);
    }
}
