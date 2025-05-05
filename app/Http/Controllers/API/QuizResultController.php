<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuizResult;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QuizResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = QuizResult::with(['quiz', 'user']);


        if ($request->has('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }


        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }



        if ($request->has('status') && in_array($request->status, ['not_started', 'in_progress', 'completed'])) {
            $query->where('status', $request->status);
        }


        if ($request->user()->isAdmin()) {
        } elseif ($request->user()->isTrainer()) {

            $trainerQuizIds = $request->user()->createdQuizzes()->pluck('id')->toArray();
            $trainerClassIds = $request->user()->trainedClasses()->pluck('id')->toArray();

            $query->where(function ($q) use ($trainerQuizIds, $trainerClassIds, $request) {
                $q->whereIn('quiz_id', $trainerQuizIds)
                    ->orWhereHas('user', function ($userQuery) use ($trainerClassIds) {
                        $userQuery->whereHas('classes', function ($classQuery) use ($trainerClassIds) {
                            $classQuery->whereIn('classes.id', $trainerClassIds);
                        });
                    });
            });
        } elseif ($request->user()->isCandidate()) {

            $query->where('user_id', $request->user()->id);
        }

        $results = $query->paginate(15);

        return response()->json($results);
    }

    /**
     * Store a newly created resource
     * Start a new quiz attempt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'required|exists:quizzes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quiz = Quiz::find($request->quiz_id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }


        if (!$quiz->is_active) {
            return response()->json(['message' => 'Quiz is not active'], 403);
        }


        if (!$this->canTakeQuiz($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $activeAttempt = QuizResult::where('quiz_id', $request->quiz_id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['in_progress'])
            ->first();

        if ($activeAttempt) {
            return response()->json([
                'message' => 'You already have an active attempt for this quiz',
                'quiz_result' => $activeAttempt
            ], 400);
        }


        $totalPoints = Question::where('quiz_id', $request->quiz_id)->sum('points');


        $quizResult = QuizResult::create([
            'quiz_id' => $request->quiz_id,
            'user_id' => $request->user()->id,
            'score' => 0,
            'total_points' => $totalPoints,
            'time_spent' => 0,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Quiz attempt started successfully',
            'quiz_result' => $quizResult
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $quizResult = QuizResult::with(['quiz', 'user', 'userAnswers.question', 'userAnswers.answer'])->find($id);

        if (!$quizResult) {
            return response()->json(['message' => 'Quiz result not found'], 404);
        }


        if (!$this->canViewQuizResult($request->user(), $quizResult)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($quizResult);
    }

    /**
     * Update the specified resource
     * Submit answers for a quiz attempt
     */
    public function update(Request $request, string $id)
    {
        $quizResult = QuizResult::find($id);

        if (!$quizResult) {
            return response()->json(['message' => 'Quiz result not found'], 404);
        }

        if ($request->user()->id != $quizResult->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($quizResult->status != 'in_progress') {
            return response()->json(['message' => 'Quiz attempt is not in progress (already completed or not started)'], 400);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_id' => 'nullable|exists:answers,id',
            'answers.*.text_answer' => 'nullable|string',
            'completed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $score = 0;

            foreach ($request->answers as $answerData) {
                $question = Question::find($answerData['question_id']);
                if ($question->quiz_id != $quizResult->quiz_id) {
                    continue;
                }

                $isCorrect = false;

                if ($question->type == 'short_answer') {
                    $correctAnswers = Answer::where('question_id', $question->id)
                        ->where('is_correct', true)
                        ->pluck('content')
                        ->toArray();
                    $isCorrect = in_array(
                        strtolower(trim($answerData['text_answer'] ?? '')),
                        array_map('strtolower', array_map('trim', $correctAnswers))
                    );
                } else {
                    if (isset($answerData['answer_id'])) {
                        $answer = Answer::find($answerData['answer_id']);
                        $isCorrect = $answer && $answer->is_correct;
                    }
                }

                UserAnswer::updateOrCreate(
                    [
                        'quiz_result_id' => $quizResult->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'answer_id' => $answerData['answer_id'] ?? null,
                        'text_answer' => $answerData['text_answer'] ?? null,
                        'is_correct' => $isCorrect,
                    ]
                );

                if ($isCorrect) {
                    $score += $question->points;
                }
            }


            $elapsed = now()->diffInSeconds($quizResult->started_at);
            $quizResult->time_spent = max(0, (int) $elapsed);
            $quizResult->score = $score;

            if ($request->filled('completed') && $request->completed) {
                $quizResult->status = 'completed';
                $quizResult->completed_at = now();
            }

            $quizResult->save();

            DB::commit();

            return response()->json([
                'message' => $quizResult->status == 'completed' ? 'Quiz completed successfully' : 'Answers saved successfully',
                'quiz_result' => $quizResult->load(['userAnswers.question', 'userAnswers.answer'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing answers: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request, string $id)
    {
        $quizResult = QuizResult::find($id);

        if (!$quizResult) {
            return response()->json(['message' => 'Quiz result not found'], 404);
        }


        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $quizResult->delete();

        return response()->json([
            'message' => 'Quiz result deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can take a quiz
     */
    private function canTakeQuiz($user, $quiz): bool
    {

        if (!$user->isCandidate()) {
            return false;
        }


        $userClassIds = $user->classes()->pluck('classes.id')->toArray();
        $quizClassIds = $quiz->classes()->pluck('classes.id')->toArray();

        return !empty(array_intersect($userClassIds, $quizClassIds));
    }

    /**
     * Check if the authenticated user can view a quiz result
     */
    private function canViewQuizResult($user, $quizResult): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->id == $quizResult->user_id) {
            return true;
        }


        if ($user->isTrainer()) {
            $quiz = Quiz::find($quizResult->quiz_id);

            if ($quiz && $quiz->created_by == $user->id) {
                return true;
            }

            $trainerClassIds = $user->trainedClasses()->pluck('id')->toArray();
            $candidateClassIds = $quizResult->user->classes()->pluck('classes.id')->toArray();

            return !empty(array_intersect($trainerClassIds, $candidateClassIds));
        }

        return false;
    }
}
