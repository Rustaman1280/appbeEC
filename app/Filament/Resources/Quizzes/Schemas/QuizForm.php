<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('category'),
                Select::make('difficulty')
                    ->options([
                        'easy' => 'Easy',
                        'medium' => 'Medium',
                        'hard' => 'Hard',
                    ])
                    ->required()
                    ->default('easy'),
                TextInput::make('time_limit')
                    ->numeric(),
                TextInput::make('total_xp')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('questions')
                    ->label('Soal')
                    ->relationship('questions', 'text')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Pilih pertanyaan yang masuk ke quiz ini'),
            ]);
    }
}
