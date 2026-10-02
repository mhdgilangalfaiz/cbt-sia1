<?php

namespace App\Filament\Resources\ExamScores;

use App\Filament\Resources\ExamScores\Pages\ListExamScores;
use App\Filament\Resources\ExamScores\Pages\ViewExamScore;
use App\Filament\Resources\ExamScores\RelationManagers\AttemptsRelationManager;
use App\Models\Exam;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExamScoreResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Ujian';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Nilai Siswa';

    protected static ?string $modelLabel = 'Nilai Ujian';

    protected static ?string $pluralModelLabel = 'Nilai Siswa';

    protected static ?string $slug = 'nilai-siswa';

    protected static ?string $recordTitleAttribute = 'title';

    // data ujian dikelola di menu Exam, bukan di sini
    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                TextEntry::make('title')
                    ->label('Ujian'),
                TextEntry::make('duration')
                    ->label('Durasi')
                    ->suffix(' menit'),
                TextEntry::make('threshold')
                    ->label('Nilai Minimal'),
                TextEntry::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d F Y, H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount([
                    'attempts' => fn (Builder $q) => $q
                        ->whereNotNull('submitted_at'),
                    'attempts as passed_count' => fn (Builder $q) => $q
                        ->whereNotNull('submitted_at')
                        ->where('passed', true),
                ])
                ->withAvg(
                    ['attempts' => fn (Builder $q) => $q->whereNotNull('submitted_at')],
                    'score'
                ))
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Jenis Ujian')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d F Y, H:i')
                    ->sortable(),
                TextColumn::make('attempts_count')
                    ->label('Peserta')
                    ->sortable(),
                TextColumn::make('passed_count')
                    ->label('Lulus'),
                TextColumn::make('attempts_avg_score')
                    ->label('Rata-rata')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat nilai siswa'),
            ]);
    }

    // daftar nilai per siswa di halaman detail ujian
    public static function getRelations(): array
    {
        return [
            AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamScores::route('/'),
            'view'  => ViewExamScore::route('/{record}'),
        ];
    }
}