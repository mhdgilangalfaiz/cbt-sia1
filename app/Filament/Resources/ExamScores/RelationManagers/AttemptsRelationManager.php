<?php

namespace App\Filament\Resources\ExamScores\RelationManagers;

use App\Models\ExamAttempt;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Nilai Siswa';

    public function table(Table $table): Table
    {
        return $table
            // hanya percobaan yang sudah dikirim/dinilai
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereNotNull('submitted_at')
                ->with('user'))
            ->defaultSort('score', 'desc')
            ->columns([
                TextColumn::make('user.username')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Nama Siswa')
                    ->searchable(),
                TextColumn::make('correct_count')
                    ->label('Benar')
                    ->formatStateUsing(fn ($state, $record) => "{$state} / {$record->total_questions}"),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('passed')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Lulus' : 'Belum lulus')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('submitted_at')
                    ->label('Dikerjakan')
                    ->dateTime('d F Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('passed')
                    ->label('Status')
                    ->trueLabel('Lulus')
                    ->falseLabel('Belum lulus'),
            ])
            ->recordActions([
                Action::make('lihatJawaban')
                    ->label('Lihat Jawaban Siswa')
                    ->icon(Heroicon::OutlinedEye)
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->modalHeading(fn (ExamAttempt $record) => 'Jawaban ' . ($record->user?->name ?? 'Siswa'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (ExamAttempt $record) => view(
                        'filament.resources.exam-scores.attempt-review',
                        [
                            'attempt' => $record,
                            'review'  => static::buildReview($record),
                        ]
                    )),
            ]);
    }

    // Menyusun soal, pilihan siswa, dan kunci jawaban untuk satu percobaan
    protected static function buildReview(ExamAttempt $attempt): array
    {
        $rows = $attempt->answers()
            ->with(['question.answers' => fn ($q) => $q->withTrashed()])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $rows->map(function ($row, $index) {
            $question = $row->question;
            $options = $question?->answers->keyBy('id') ?? collect();

            // urutan pilihan sama seperti yang dilihat siswa saat ujian
            $ordered = collect($row->option_order ?? [])
                ->map(fn ($id) => $options->get($id))
                ->filter();

            // percobaan lama (sebelum fitur timer) tidak punya urutan tersimpan
            if ($ordered->isEmpty()) {
                $ordered = $options->values();
            }

            return [
                'number'     => $index + 1,
                'payload'    => $question?->payload,
                'answered'   => $row->answer_id !== null,
                'is_correct' => (bool) $row->is_correct,
                'options'    => $ordered->map(fn ($a) => [
                    'text'   => $a->text,
                    'chosen' => $a->id == $row->answer_id,
                    'is_key' => (bool) $a->is_correct,
                ])->values()->all(),
            ];
        })->all();
    }
}