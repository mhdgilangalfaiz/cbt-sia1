<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Answer;
use App\Models\Question;
use Dom\Text;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subject.name')
                    ->label('Subject'),
                TextEntry::make('payload')
                    ->label('Pertanyaan')
                    ->html()
                    ->columnSpanFull(),
                TextEntry::make('score')
                    ->formatStateUsing(function ($state) {
                        return $state . ' poin';
                    })
                    ->numeric(),
                // pilihan jawaban
                RepeatableEntry::make('answers')
                    ->label('Pilihan jawaban')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        TextEntry::make('text')
                            ->label('Deskripsi pilihan'),
                        IconEntry::make('is_correct')
                            ->label('Benar')
                    ]),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Question $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
