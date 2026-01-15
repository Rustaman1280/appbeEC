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
        $user = $request->user();

        $existingAttempt = QuizAttempt::with('answers.question')
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->latest('completed_at')
            ->first();

        if ($request->missing('answers')) {
            if ($existingAttempt) {
                $this->backfillXpIfMissing($existingAttempt, $user);

                return response()->json(
                    $this->buildAttemptResponse($existingAttempt->fresh('answers.question'), $user->fresh(), true)
                );
            }

            // If no previous attempt and no answers are sent, return a neutral payload
            // so frontend can show state without throwing validation errors.
            return response()->json([
                'attempt' => [
                    'score' => 0,
                    'correct_answers' => 0,
                    'total_questions' => $quiz->questions()->count(),
                    'xp_earned' => 0,
                    'bonus' => 0,
                    'already_attempted' => false,
                    'answers' => [],
                ],
                'user' => new UserResource($user),
            ]);
        }

        if ($existingAttempt) {
            $this->backfillXpIfMissing($existingAttempt, $user);

            return response()->json(
                $this->buildAttemptResponse($existingAttempt->fresh('answers.question'), $user->fresh(), true)
            );
        }

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

                // Fallback XP if question has 0/NULL reward
                $questionXp = $question->xp_reward ?: config('xp.default_question_xp.' . ($question->difficulty ?? 'easy'), 0);
                $score += $questionXp;
            }

            $answerPayload[] = [
                'question_id' => $question->id,
                'selected_answer' => (int) $answer['selected_answer'],
                'is_correct' => $isCorrect,
                'time_taken' => $answer['time_taken'] ?? 0,
            ];
        }

        $completionXp = config('xp.quiz_completion', 50);
        $bonus = $correctCount === $totalQuestions && $totalQuestions > 0 ? config('xp.perfect_score', 100) : 0;
        $earned = max(0, $score + $completionXp + $bonus);

        try {
            DB::beginTransaction();

            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
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

            // Use atomic increments to guarantee XP is persisted
            $user->increment('xp', $earned);
            $user->increment('total_xp', $earned);

            // Log XP transaction for audit/recalc
            DB::table('xp_transactions')->insert([
                'user_id' => $user->id,
                'source' => 'quiz_attempt',
                'reference_id' => $attempt->id,
                'amount' => $earned,
                'description' => 'Quiz #' . $quiz->id . ' attempt',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Failed to save quiz attempt', ['error' => $th->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan hasil quiz',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $attempt->load('answers.question');

        $user->refresh();

        return response()->json(
            $this->buildAttemptResponse($attempt, $user, false, $bonus)
        );
    }

    private function buildAttemptResponse(QuizAttempt $attempt, $user, bool $alreadyAttempted = false, ?int $bonus = null): array
    {
        $attempt->loadMissing('answers.question');

        $completionXp = config('xp.quiz_completion', 50);
        $calculatedBonus = $bonus ?? max(0, $attempt->xp_earned - ($attempt->score + $completionXp));

        $answers = $attempt->answers->map(function ($answer) {
            $question = $answer->question;

            return [
                'question_id' => $answer->question_id,
                'question' => $question?->text,
                'options' => $question?->options,
                'selected_answer' => $answer->selected_answer,
                'correct_answer' => $question?->correct_answer,
                'is_correct' => $answer->is_correct,
                'explanation' => $question?->explanation,
            ];
        });

        return [
            'attempt' => [
                'score' => $attempt->score,
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'xp_earned' => $attempt->xp_earned,
                'bonus' => $calculatedBonus,
                'already_attempted' => $alreadyAttempted,
                'answers' => $answers,
            ],
            'user' => new UserResource($user),
        ];
    }

    private function backfillXpIfMissing(QuizAttempt $attempt, $user): void
    {
        $transaction = DB::table('xp_transactions')
            ->where('source', 'quiz_attempt')
            ->where('reference_id', $attempt->id)
            ->first();

        if ($transaction && (int) $transaction->amount > 0 && $attempt->xp_earned > 0) {
            return;
        }

        $attempt->loadMissing('answers.question', 'quiz.questions');

        $answers = $attempt->answers;
        $totalQuestions = $attempt->quiz?->questions?->count() ?? $attempt->total_questions;
        $correctCount = 0;
        $score = 0;

        foreach ($answers as $answer) {
            if (! $answer->is_correct) {
                continue;
            }

            $correctCount += 1;

            $question = $answer->question;
            $questionXp = $question?->xp_reward ?: config('xp.default_question_xp.' . ($question?->difficulty ?? 'easy'), 0);
            $score += $questionXp;
        }

        $completionXp = config('xp.quiz_completion', 50);
        $bonus = $correctCount === $totalQuestions && $totalQuestions > 0 ? config('xp.perfect_score', 100) : 0;
        $earned = max(0, $score + $completionXp + $bonus);

        if ($earned <= 0) {
            return;
        }

        $previousEarned = (int) $attempt->xp_earned;
        $delta = max(0, $earned - $previousEarned);

        DB::transaction(function () use ($attempt, $user, $earned, $score, $correctCount, $totalQuestions, $delta, $transaction) {
            $attempt->update([
                'score' => $score,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'xp_earned' => $earned,
            ]);

            if ($delta > 0) {
                $user->increment('xp', $delta);
                $user->increment('total_xp', $delta);
            }

            if ($transaction) {
                DB::table('xp_transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'amount' => $earned,
                        'description' => 'Quiz #' . $attempt->quiz_id . ' attempt (backfill)',
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('xp_transactions')->insert([
                    'user_id' => $user->id,
                    'source' => 'quiz_attempt',
                    'reference_id' => $attempt->id,
                    'amount' => $earned,
                    'description' => 'Quiz #' . $attempt->quiz_id . ' attempt (backfill)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
