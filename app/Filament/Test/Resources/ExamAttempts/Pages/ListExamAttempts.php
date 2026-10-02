<?php

namespace App\Filament\Test\Resources\ExamAttempts\Pages;

use App\Filament\Test\Resources\ExamAttempts\ExamAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListExamAttempts extends ListRecords
{
    protected static string $resource = ExamAttemptResource::class;
}