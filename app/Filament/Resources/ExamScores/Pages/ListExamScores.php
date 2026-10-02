<?php

namespace App\Filament\Resources\ExamScores\Pages;

use App\Filament\Resources\ExamScores\ExamScoreResource;
use Filament\Resources\Pages\ListRecords;

class ListExamScores extends ListRecords
{
    protected static string $resource = ExamScoreResource::class;
}