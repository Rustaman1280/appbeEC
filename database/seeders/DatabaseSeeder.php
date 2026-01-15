<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['admin', 'staff', 'member'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@englishclub.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'level' => 30,
                'xp' => 2500,
                'total_xp' => 45000,
                'streak' => 15,
            ],
        );
        $admin->syncRoles(['admin']);

        $staff = User::updateOrCreate(
            ['email' => 'staff@englishclub.com'],
            [
                'name' => 'Sarah Pengurus',
                'username' => 'staff1',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'level' => 25,
                'xp' => 1800,
                'total_xp' => 38000,
                'streak' => 10,
            ],
        );
        $staff->syncRoles(['staff']);

        $member1 = User::updateOrCreate(
            ['email' => 'member1@student.com'],
            [
                'name' => 'Alex Student',
                'username' => 'member1',
                'password' => Hash::make('member123'),
                'role' => 'member',
                'level' => 20,
                'xp' => 1200,
                'total_xp' => 30000,
                'streak' => 7,
            ],
        );
        $member1->syncRoles(['member']);

        $member2 = User::updateOrCreate(
            ['email' => 'member2@student.com'],
            [
                'name' => 'Emma Learner',
                'username' => 'member2',
                'password' => Hash::make('member123'),
                'role' => 'member',
                'level' => 18,
                'xp' => 800,
                'total_xp' => 27000,
                'streak' => 5,
            ],
        );
        $member2->syncRoles(['member']);

        $questions = collect([
            [
                'text' => 'She ___ to school every day.',
                'options' => ['go', 'goes', 'going', 'gone'],
                'correct_answer' => 1,
                'category' => 'grammar',
                'difficulty' => 'easy',
                'xp_reward' => 10,
                'explanation' => 'Use "goes" for third person singular in present simple tense.',
            ],
            [
                'text' => 'I have ___ finished my homework.',
                'options' => ['yet', 'already', 'still', 'never'],
                'correct_answer' => 1,
                'category' => 'grammar',
                'difficulty' => 'medium',
                'xp_reward' => 20,
                'explanation' => '"Already" is used in positive sentences with present perfect.',
            ],
            [
                'text' => 'If I ___ rich, I would travel the world.',
                'options' => ['am', 'was', 'were', 'will be'],
                'correct_answer' => 2,
                'category' => 'tenses',
                'difficulty' => 'hard',
                'xp_reward' => 30,
                'explanation' => 'Second conditional uses "were" for all persons.',
            ],
            [
                'text' => 'The book is ___ the table.',
                'options' => ['in', 'on', 'at', 'by'],
                'correct_answer' => 1,
                'category' => 'preposition',
                'difficulty' => 'easy',
                'xp_reward' => 10,
                'explanation' => 'Use "on" for objects on surfaces.',
            ],
            [
                'text' => 'What does "meticulous" mean?',
                'options' => ['Careless', 'Very careful', 'Quick', 'Lazy'],
                'correct_answer' => 1,
                'category' => 'vocabulary',
                'difficulty' => 'medium',
                'xp_reward' => 20,
                'explanation' => 'Meticulous means showing great attention to detail; very careful.',
            ],
        ])->map(fn ($question) => Question::create($question));

        $quiz1 = Quiz::create([
            'title' => 'Present Simple Tense',
            'description' => 'Test your knowledge of present simple tense',
            'category' => 'grammar',
            'difficulty' => 'easy',
            'time_limit' => 120,
            'total_xp' => 100,
        ]);

        $quiz2 = Quiz::create([
            'title' => 'Advanced Grammar',
            'description' => 'Challenge yourself with advanced grammar questions',
            'category' => 'grammar',
            'difficulty' => 'hard',
            'time_limit' => 180,
            'total_xp' => 200,
        ]);

        $quiz3 = Quiz::create([
            'title' => 'Vocabulary Builder',
            'description' => 'Expand your English vocabulary',
            'category' => 'vocabulary',
            'difficulty' => 'medium',
            'time_limit' => 60,
            'total_xp' => 150,
        ]);

        $quiz1->questions()->sync([
            $questions[0]->id => ['sort_order' => 1, 'points' => 50],
            $questions[1]->id => ['sort_order' => 2, 'points' => 50],
        ]);

        $quiz2->questions()->sync([
            $questions[2]->id => ['sort_order' => 1, 'points' => 100],
            $questions[3]->id => ['sort_order' => 2, 'points' => 100],
        ]);

        $quiz3->questions()->sync([
            $questions[4]->id => ['sort_order' => 1, 'points' => 150],
        ]);
    }
}
