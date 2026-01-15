<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('text')
                    ->label('Pertanyaan')
                    ->required()
                    ->columnSpanFull(),
                TagsInput::make('options')
                    ->label('Opsi jawaban')
                    ->required()
                    ->placeholder('Tambah opsi')
                    ->suggestions([])
                    ->helperText('Urutkan sesuai pilihan A, B, C ...'),
                Select::make('correct_answer')
                    ->label('Jawaban benar')
                    ->options(fn (Get $get) => collect($get('options') ?? [])
                        ->mapWithKeys(fn ($option, $index) => [$index => $option]))
                    ->required(),
                Select::make('category')
                    ->options([
                        'grammar' => 'Grammar',
                        'vocabulary' => 'Vocabulary',
                        'tenses' => 'Tenses',
                        'preposition' => 'Prepositions',
                    ])
                    ->required()
                    ->searchable(),
                Select::make('difficulty')
                    ->options([
                        'easy' => 'Easy',
                        'medium' => 'Medium',
                        'hard' => 'Hard',
                    ])
                    ->required()
                    ->default('easy'),
                TextInput::make('xp_reward')
                    ->label('XP Reward')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('explanation')
                    ->label('Penjelasan')
                    ->columnSpanFull(),
            ]);
    }
}
