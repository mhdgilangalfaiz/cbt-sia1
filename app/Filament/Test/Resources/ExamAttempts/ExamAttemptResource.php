<?php

namespace App\Filament\Test\Resources\ExamAttempts;

use App\Filament\Test\Resources\ExamAttempts\Pages\ListExamAttempts;
use App\Filament\Test\Resources\ExamAttempts\Pages\ViewExamAttempt;
use App\Models\ExamAttempt;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExamAttemptResource extends Resource
{
    protected static ?string $model = ExamAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Riwayat Ujian';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Riwayat Ujian';

    protected static ?string $pluralModelLabel = 'Riwayat Ujian';

    // ini yang membuat URL menjadi /ujian/riwayat
    protected static ?string $slug = 'riwayat';

    // siswa hanya boleh melihat riwayat miliknya sendiri
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->whereNotNull('submitted_at')
            ->with('exam');
    }

    // riwayat dibuat otomatis saat siswa mengirim jawaban, bukan diisi manual
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->label('Dikerjakan')
                    ->dateTime('d F Y, H:i')
                    ->sortable(),
                TextColumn::make('correct_count')
                    ->label('Benar')
                    ->formatStateUsing(fn ($state, $record) => "{$state} / {$record->total_questions}"),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('passed')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Lulus' : 'Belum lulus')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
            ])
            ->recordActions([
            Action::make('detail')
                ->label('Lihat detail')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamAttempts::route('/'),
            'view'  => ViewExamAttempt::route('/{record}'),
        ];
    }
}