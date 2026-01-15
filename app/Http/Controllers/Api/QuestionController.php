<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Question::latest()->paginate(25)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string'],
            'correct_answer' => ['required', 'integer', 'min:0'],
            'category' => ['required', 'string', 'max:100'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'xp_reward' => ['nullable', 'integer', 'min:0'],
            'explanation' => ['nullable', 'string'],
        ]);

        $question = Question::create($data);

        return response()->json(['data' => $question], Response::HTTP_CREATED);
    }

    public function update(Request $request, Question $question)
    {
        $data = $request->validate([
            'text' => ['sometimes', 'string'],
            'options' => ['sometimes', 'array', 'min:2'],
            'options.*' => ['required_with:options', 'string'],
            'correct_answer' => ['sometimes', 'integer', 'min:0'],
            'category' => ['sometimes', 'string', 'max:100'],
            'difficulty' => ['sometimes', 'in:easy,medium,hard'],
            'xp_reward' => ['nullable', 'integer', 'min:0'],
            'explanation' => ['nullable', 'string'],
        ]);

        $question->update($data);

        return response()->json(['data' => $question]);
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return response()->json(['message' => 'Question deleted']);
    }
}
