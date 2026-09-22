<?php

namespace App\Filament\Test\Resources\Exams;

use App\Filament\Test\Resources\Exams\Pages\ManageExams;
use App\Filament\Test\Resources\Exams\Pages\StartingExam;
use App\Models\Exam;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Sesi Ujian';

    protected static ?string $modelLabel = 'Ujian';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (EloquentBuilder $query) {
                $now = now();
                $query
                    ->where('is_available', true)
                    ->where(function (EloquentBuilder $query) use ($now) {
                        $query
                            ->where(function (EloquentBuilder $query) use ($now) {
                                $query
                                    ->where('exact_time', true)
                                    ->where('started_at', '>=', $now)
                                    ->whereRaw(
                                        'DATE_ADD(started_at, INTERVAL duration MINUTE) >= ?',
                                        [$now]
                                    );
                            })
                            ->orWhere(function (EloquentBuilder $query) use ($now) {
                                $query
                                    ->whereNotNull('expired_at')
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
                    ->url(fn ($record) => 
                        route(
                            StartingExam::getRouteName(),
                            ['exam' => $record]
                        )
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExams::route('/'),
            'mulai' => StartingExam::route('/{exam}/mulai')
        ];
    }
}