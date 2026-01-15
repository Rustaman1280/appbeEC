<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $records = Attendance::with(['user', 'markedBy'])
            ->whereDate('date', $date)
            ->get();

        return response()->json([
            'data' => $records,
            'date' => $date,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:present,absent,late'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $xp = $data['status'] === 'present' ? config('xp.attendance', 30) : 0;

        $record = DB::transaction(function () use ($data, $xp, $request) {
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'date' => $data['date'],
                ],
                [
                    'status' => $data['status'],
                    'marked_by' => $request->user()->id,
                    'notes' => $data['notes'] ?? null,
                    'xp_awarded' => $xp,
                ],
            );

            if ($xp > 0) {
                $user = User::find($data['user_id']);

                if ($user) {
                    $user->increment('xp', $xp);
                    $user->increment('total_xp', $xp);

                    DB::table('xp_transactions')->insert([
                        'user_id' => $user->id,
                        'source' => 'attendance',
                        'reference_id' => $attendance->id,
                        'amount' => $xp,
                        'description' => 'Attendance on ' . $data['date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return $attendance;
        });

        return response()->json(['data' => $record], Response::HTTP_CREATED);
    }
}
