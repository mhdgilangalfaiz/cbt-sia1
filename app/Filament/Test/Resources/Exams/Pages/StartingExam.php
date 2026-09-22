<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Exam;
use Filament\Resources\Pages\Page;

class StartingExam extends Page
{
    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.test.resources.exams.pages.starting-exam';

    public array $collections = [];

    public function mount(Exam $exam) {
        $pelajarans = null;
        if ($exam) $pelajarans = $exam->subjects;

        foreach ($pelajarans as $mapel) {
            $collections[] = [
                'id'    => $mapel->id,
                'name'  => $mapel->name,
                'soals' => $mapel
                            ->questions()
                            ->where('is_active', true)
                            ->with([
                                'answers' => fn ($query) =>
                                $query->inRandomOrder(),
                            ])
                            ->inRandomOrder()
                            ->limit($mapel->pivot->qty)
                            ->get()
                            ->toArray(),
            ];
        }
    }
}
