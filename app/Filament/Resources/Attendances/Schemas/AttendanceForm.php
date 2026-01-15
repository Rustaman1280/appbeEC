<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                Select::make('status')
                    ->options([
                        'present' => 'Hadir',
                        'absent' => 'Tidak hadir',
                        'late' => 'Telat',
                    ])
                    ->required()
                    ->default('present'),
                Hidden::make('marked_by')
                    ->default(fn () => Auth::id())
                    ->dehydrated(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('xp_awarded')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
