<?php

namespace App\Filament\Test\Resources\Exams;

use App\Filament\Test\Resources\Exams\Pages\ManageExams;
use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Sesi Ujian';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Ujian';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (EloquentBuilder $query) {
                $now = now();

                $query
                    ->where('is_available', true)
                    ->where(function (EloquentBuilder $q) use ($now) {
                        // Mode waktu pasti: tampil selama belum lewat started_at + durasi
                        $q->where(function (EloquentBuilder $q) use ($now) {
                            $q->where('exact_time', true)
                                ->whereRaw(
                                    'DATE_ADD(started_at, INTERVAL duration MINUTE) >= ?',
                                    [$now]
                                );
                        })
                        // Mode rentang waktu: tampil selama belum lewat expired_at
                        ->orWhere(function (EloquentBuilder $q) use ($now) {
                            $q->where('exact_time', false)
                                ->where('expired_at', '>=', $now);
                        });
                    });
            })
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('threshold')
                    ->label('Min. Score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d F Y, H:i:s')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('Mulai ujian')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('primary')
                    ->button()
                    ->disabled(fn ($record) => $record->started_at >= now())
                    ->url(fn ($record) => route(
                        StartingExam::getRouteName(),
                        ['exam' => $record]
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
            'mulai' => StartingExam::route('/{exam}/mulai'),
        ];
    }
}