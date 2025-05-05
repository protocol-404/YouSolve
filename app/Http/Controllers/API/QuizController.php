<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Category;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Quiz::with(['category', 'creator', 'questions']);


        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }


        if ($request->has('difficulty') && in_array($request->difficulty, ['beginner', 'intermediate', 'advanced'])) {
            $query->where('difficulty', $request->difficulty);
        }


        if ($request->user()->isTrainer()) {
            $query->where('created_by', $request->user()->id);
        }


        if ($request->user()->isCandidate()) {
            $userClassIds = $request->user()->classes()->pluck('classes.id');

            $query->where('is_active', true)
                  ->whereHas('classes', function($q) use ($userClassIds) {
                      $q->whereIn('classes.id', $userClassIds);
                  });
        }

        $quizzes = $query->paginate(15);

        return response()->json($quizzes);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {

        if (!$request->user()->isTrainer() && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'time_limit' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'class_ids' => 'array',
            'class_ids.*' => 'exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->user()->isTrainer() && $request->has('class_ids')) {
            $trainerClassIds = $request->user()->trainedClasses()->pluck('id')->toArray();
            $invalidClassIds = array_diff($request->class_ids, $trainerClassIds);

            if (!empty($invalidClassIds)) {
                return response()->json([
                    'message' => 'You can only assign quizzes to your own classes',
                    'invalid_class_ids' => $invalidClassIds
                ], 403);
            }
        }

        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'created_by' => $request->user()->id,
            'difficulty' => $request->difficulty,
            'time_limit' => $request->time_limit,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);


        if ($request->has('class_ids') && !empty($request->class_ids)) {
            $quiz->classes()->attach($request->class_ids);
        }

        return response()->json([
            'message' => 'Quiz created successfully',
            'quiz' => $quiz->load(['category', 'creator', 'classes'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $quiz = Quiz::with(['category', 'creator', 'questions.answers', 'classes'])->find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }


        if (!$this->canViewQuiz($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($quiz);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }


        if (!$this->canUpdateQuiz($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'difficulty' => 'sometimes|in:beginner,intermediate,advanced',
            'time_limit' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'class_ids' => 'sometimes|array',
            'class_ids.*' => 'exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->user()->isTrainer() && $request->has('class_ids')) {
            $trainerClassIds = $request->user()->trainedClasses()->pluck('id')->toArray();
            $invalidClassIds = array_diff($request->class_ids, $trainerClassIds);

            if (!empty($invalidClassIds)) {
                return response()->json([
                    'message' => 'You can only assign quizzes to your own classes',
                    'invalid_class_ids' => $invalidClassIds
                ], 403);
            }
        }


        if ($request->has('title')) $quiz->title = $request->title;
        if ($request->has('description')) $quiz->description = $request->description;
        if ($request->has('category_id')) $quiz->category_id = $request->category_id;
        if ($request->has('difficulty')) $quiz->difficulty = $request->difficulty;
        if ($request->has('time_limit')) $quiz->time_limit = $request->time_limit;
        if ($request->has('is_active')) $quiz->is_active = $request->is_active;

        $quiz->save();


        if ($request->has('class_ids')) {
            $quiz->classes()->sync($request->class_ids);
        }

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => $quiz->load(['category', 'creator', 'classes'])
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request, string $id)
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }


        if (!$this->canDeleteQuiz($request->user(), $quiz)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $quiz->delete();

        return response()->json([
            'message' => 'Quiz deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can view the quiz
     */
    private function canViewQuiz($user, $quiz): bool
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
     * Check if the authenticated user can update the quiz
     */
    private function canUpdateQuiz($user, $quiz): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->isTrainer() && $quiz->created_by == $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if the authenticated user can delete the quiz
     */
    private function canDeleteQuiz($user, $quiz): bool
    {

        return $this->canUpdateQuiz($user, $quiz);
    }
}
