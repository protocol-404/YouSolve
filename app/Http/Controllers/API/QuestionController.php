<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (!$request->has('quiz_id')) {
            return response()->json(['message' => 'Quiz ID is required'], 400);
        }

        $quiz = Quiz::find($request->quiz_id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }


        if (!$this->canViewQuizQuestions($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $questions = Question::with('answers')
                            ->where('quiz_id', $request->quiz_id)
                            ->orderBy('order', 'asc')
                            ->get();

        return response()->json($questions);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'required|exists:quizzes,id',
            'content' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quiz = Quiz::find($request->quiz_id);


        if (!$this->canManageQuizQuestions($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        if (!$request->has('order')) {
            $maxOrder = Question::where('quiz_id', $request->quiz_id)->max('order') ?? -1;
            $order = $maxOrder + 1;
        } else {
            $order = $request->order;
        }

        $question = Question::create([
            'quiz_id' => $request->quiz_id,
            'content' => $request->content,
            'type' => $request->type,
            'explanation' => $request->explanation,
            'points' => $request->points,
            'order' => $order,
        ]);

        return response()->json([
            'message' => 'Question created successfully',
            'question' => $question
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $question = Question::with('answers')->find($id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canViewQuizQuestions($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($question);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canManageQuizQuestions($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|string',
            'type' => 'sometimes|in:multiple_choice,true_false,short_answer',
            'explanation' => 'nullable|string',
            'points' => 'sometimes|integer|min:1',
            'order' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->has('content')) $question->content = $request->content;
        if ($request->has('type')) $question->type = $request->type;
        if ($request->has('explanation')) $question->explanation = $request->explanation;
        if ($request->has('points')) $question->points = $request->points;
        if ($request->has('order')) $question->order = $request->order;

        $question->save();

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request, string $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canManageQuizQuestions($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can view questions for a quiz
     */
    private function canViewQuizQuestions($user, $quiz): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->isTrainer() && $quiz->created_by == $user->id) {
            return true;
        }


        if ($user->isCandidate()) {
            $userClassIds = $user->classes()->pluck('classes.id')->toArray();
            $quizClassIds = $quiz->classes()->pluck('classes.id')->toArray();

            return $quiz->is_active && !empty(array_intersect($userClassIds, $quizClassIds));
        }

        return false;
    }

    /**
     * Check if the authenticated user can manage questions for a quiz
     */
    private function canManageQuizQuestions($user, $quiz): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->isTrainer() && $quiz->created_by == $user->id) {
            return true;
        }

        return false;
    }
}
