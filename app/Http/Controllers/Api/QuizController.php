<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Http\Resources\UserResource;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::withCount('questions')->latest()->get();

        return QuizResource::collection($quizzes);
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions');

        return new QuizResource($quiz);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'time_limit' => ['nullable', 'integer', 'min:0'],
            'total_xp' => ['nullable', 'integer', 'min:0'],
            'questions' => ['array'],
            'questions.*.id' => ['required', 'exists:questions,id'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'questions.*.points' => ['nullable', 'integer', 'min:0'],
        ]);

        $quiz = Quiz::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'difficulty' => $data['difficulty'],
            'time_limit' => $data['time_limit'] ?? null,
            'total_xp' => $data['total_xp'] ?? 0,
        ]);

        if (! empty($data['questions'])) {
            $pivot = collect($data['questions'])->mapWithKeys(function ($item) {
                return [
                    $item['id'] => [
                        'sort_order' => $item['sort_order'] ?? 1,
                        'points' => $item['points'] ?? 0,
                    ],
                ];
            });

            $quiz->questions()->sync($pivot);
        }

        return new QuizResource($quiz->fresh(['questions']));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'time_limit' => ['nullable', 'integer', 'min:0'],
            'total_xp' => ['nullable', 'integer', 'min:0'],
            'questions' => ['array'],
            'questions.*.id' => ['required_with:questions', 'exists:questions,id'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'questions.*.points' => ['nullable', 'integer', 'min:0'],
        ]);

        $quiz->fill($request->only(['title', 'description', 'category', 'difficulty', 'time_limit', 'total_xp']));
        $quiz->save();

        if (array_key_exists('questions', $data)) {
            $pivot = collect($data['questions'] ?? [])->mapWithKeys(function ($item) {
                return [
                    $item['id'] => [
                        'sort_order' => $item['sort_order'] ?? 1,
                        'points' => $item['points'] ?? 0,
                    ],
                ];
            });

            $quiz->questions()->sync($pivot);
        }

        return new QuizResource($quiz->fresh(['questions']));
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted']);
    }

    public function submitAttempt(Request $request, Quiz $quiz)
    {
        $quiz->load('questions');

        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.selected_answer' => ['required', 'integer'],
            'answers.*.time_taken' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $answers = collect($validator->validated()['answers']);
        $questions = $quiz->questions->keyBy('id');
        $totalQuestions = $quiz->questions->count();

        $correctCount = 0;
        $score = 0;
        $answerPayload = [];

        foreach ($answers as $answer) {
            if (! $questions->has($answer['question_id'])) {
                continue;
            }

            $question = $questions[$answer['question_id']];
            $isCorrect = (int) $answer['selected_answer'] === (int) $question->correct_answer;

            if ($isCorrect) {
                $correctCount += 1;
                $score += $question->xp_reward;
            }

            $answerPayload[] = [
                'question_id' => $question->id,
                'selected_answer' => (int) $answer['selected_answer'],
                'is_correct' => $isCorrect,
                'time_taken' => $answer['time_taken'] ?? 0,
            ];
        }

        $bonus = $correctCount === $totalQuestions ? config('xp.perfect_score', 100) : 0;
        $earned = $score + config('xp.quiz_completion', 50) + $bonus;

        try {
            DB::beginTransaction();

            $attempt = QuizAttempt::create([
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz->id,
                'score' => $score,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'xp_earned' => $earned,
                'completed_at' => now(),
            ]);

            foreach ($answerPayload as $payload) {
                QuizAnswer::create(array_merge($payload, [
                    'quiz_attempt_id' => $attempt->id,
                ]));
            }

            $user = $request->user();
            $user->xp += $earned;
            $user->total_xp += $earned;
            $user->save();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Failed to save quiz attempt', ['error' => $th->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan hasil quiz',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'attempt' => [
                'score' => $score,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'xp_earned' => $earned,
                'bonus' => $bonus,
            ],
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }
}
