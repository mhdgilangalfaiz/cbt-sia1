<?php

namespace App\Filament\Resources\Exams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Jenis Ujian')
                    ->searchable(),
                TextColumn::make('duration')
                    ->numeric()
                    ->label('Durasi')
                    ->sortable(),
                TextColumn::make('threshold')
                    ->numeric()
                    ->label('Min. Nilai')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->label('Mulai')
                    ->sortable(),
                TextColumn::make('expired_at')
                    ->dateTime()
                    ->label('Berakhir')
                    ->sortable(),
                IconColumn::make('exact_time')
                    ->boolean(),
                IconColumn::make('is_available')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}