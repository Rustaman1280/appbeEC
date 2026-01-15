<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

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
                    ->live()
                    ->helperText('Urutkan sesuai pilihan A, B, C ...'),
                Select::make('correct_answer')
                    ->label('Jawaban benar')
                    ->reactive()
                    ->options(function (Get $get): array {
                        $options = $get('options');

                        if (! is_array($options)) {
                            return [];
                        }

                        return collect($options)
                            ->mapWithKeys(fn ($option, $index) => [(string) $index => $option])
                            ->all();
                    })
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
