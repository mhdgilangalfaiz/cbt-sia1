<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageExams extends ManageRecords
{
    protected static string $resource = 
    ExamResource::class;

}
