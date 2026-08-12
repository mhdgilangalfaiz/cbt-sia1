<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    /**
     * Pastikan tepat 1 pilihan jawaban yang ditandai benar sebelum soal disimpan.
     */
    protected function beforeCreate(): void
    {
        $answers = $this->form->getRawState()['answers'] ?? [];

        $correctCount = collect($answers)
            ->filter(fn (array $answer) => (bool) ($answer['is_correct'] ?? false))
            ->count();

        if ($correctCount !== 1) {
            Notification::make()
                ->title('Gagal menyimpan soal')
                ->body('Pastikan tepat 1 pilihan jawaban yang ditandai sebagai "Jawaban Benar" (saat ini: '.$correctCount.').')
                ->danger()
                ->send();

            throw new Halt();
        }
    }
}