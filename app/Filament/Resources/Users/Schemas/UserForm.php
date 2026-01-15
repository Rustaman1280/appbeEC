<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->required(fn (string $operation) => $operation === 'create'),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'pengurus' => 'Pengurus',
                        'member' => 'Member',
                    ])
                    ->required()
                    ->default('member'),
                TextInput::make('avatar'),
                TextInput::make('level')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('xp')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_xp')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('streak')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
