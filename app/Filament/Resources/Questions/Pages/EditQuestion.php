<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Pastikan tepat 1 pilihan jawaban yang ditandai benar sebelum perubahan disimpan.
     */
    protected function beforeSave(): void
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