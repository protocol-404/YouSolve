<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Validator;

class AnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if (!$request->has('question_id')) {
            return response()->json(['message' => 'Question ID is required'], 400);
        }

        $question = Question::find($request->question_id);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canViewQuestionAnswers($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $answers = Answer::where('question_id', $request->question_id)->get();

        return response()->json($answers);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'content' => 'required|string',
            'is_correct' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $question = Question::find($request->question_id);
        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canManageQuestionAnswers($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        if ($question->type != 'short_answer') {

            if ($question->type == 'true_false') {
                $existingCorrectAnswers = Answer::where('question_id', $request->question_id)
                                               ->where('is_correct', true)
                                               ->count();

                if ($existingCorrectAnswers > 0 && $request->is_correct) {
                    return response()->json([
                        'message' => 'True/False questions can only have one correct answer'
                    ], 422);
                }
            }


            if ($question->type == 'multiple_choice' && !$request->is_correct) {
                $existingCorrectAnswers = Answer::where('question_id', $request->question_id)
                                               ->where('is_correct', true)
                                               ->count();

                if ($existingCorrectAnswers == 0) {
                    return response()->json([
                        'message' => 'Multiple choice questions must have at least one correct answer'
                    ], 422);
                }
            }
        }

        $answer = Answer::create([
            'question_id' => $request->question_id,
            'content' => $request->content,
            'is_correct' => $request->is_correct,
        ]);

        return response()->json([
            'message' => 'Answer created successfully',
            'answer' => $answer
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $answer = Answer::find($id);

        if (!$answer) {
            return response()->json(['message' => 'Answer not found'], 404);
        }

        $question = Question::find($answer->question_id);
        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canViewQuestionAnswers($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($answer);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $answer = Answer::find($id);

        if (!$answer) {
            return response()->json(['message' => 'Answer not found'], 404);
        }

        $question = Question::find($answer->question_id);
        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canManageQuestionAnswers($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|string',
            'is_correct' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($question->type != 'short_answer' && $request->has('is_correct')) {

            if ($question->type == 'true_false' && $request->is_correct) {
                $existingCorrectAnswers = Answer::where('question_id', $answer->question_id)
                                               ->where('id', '!=', $id)
                                               ->where('is_correct', true)
                                               ->count();

                if ($existingCorrectAnswers > 0) {
                    return response()->json([
                        'message' => 'True/False questions can only have one correct answer'
                    ], 422);
                }
            }


            if ($question->type == 'multiple_choice' && !$request->is_correct && $answer->is_correct) {
                $existingCorrectAnswers = Answer::where('question_id', $answer->question_id)
                                               ->where('id', '!=', $id)
                                               ->where('is_correct', true)
                                               ->count();

                if ($existingCorrectAnswers == 0) {
                    return response()->json([
                        'message' => 'Multiple choice questions must have at least one correct answer'
                    ], 422);
                }
            }
        }


        if ($request->has('content')) $answer->content = $request->content;
        if ($request->has('is_correct')) $answer->is_correct = $request->is_correct;

        $answer->save();

        return response()->json([
            'message' => 'Answer updated successfully',
            'answer' => $answer
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request, string $id)
    {
        $answer = Answer::find($id);

        if (!$answer) {
            return response()->json(['message' => 'Answer not found'], 404);
        }

        $question = Question::find($answer->question_id);
        $quiz = Quiz::find($question->quiz_id);


        if (!$this->canManageQuestionAnswers($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        if ($question->type == 'multiple_choice' && $answer->is_correct) {
            $correctAnswersCount = Answer::where('question_id', $answer->question_id)
                                        ->where('is_correct', true)
                                        ->count();

            if ($correctAnswersCount <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the only correct answer for a multiple choice question'
                ], 422);
            }
        }


        $answer->delete();

        return response()->json([
            'message' => 'Answer deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can view answers for a question
     */
    private function canViewQuestionAnswers($user, $quiz): bool
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
     * Check if the authenticated user can manage answers for a question
     */
    private function canManageQuestionAnswers($user, $quiz): bool
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
