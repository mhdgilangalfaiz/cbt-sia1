<?php

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Subjects\Pages\ManageSubjects;
use App\Models\Subject;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;
use UnitEnum;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Basic Data';

    protected static ?string $modelLabel = 'Pelajaran';

    protected static ?string $navigationLabel = 'Data Pelajaran';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Mata Pelajaran')
                    ->placeholder('Nama Mata Pelajaran')
                    ->columnSpanFull()
                    ->unique('subjects', 'name')
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->placeholder('Keterangan Tambahan')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Tersedia')
                    ->default(true)
                    ->inline(false)
                    ->live()
                    ->helperText(
                        fn ($state) => $state
                            ? 'Pelajaran ini tersedia untuk diujiankan.'
                            : 'Pelajaran ini tidak tersedia untuk diujiankan.'
                            )
                    // ->helperText('Pelajaran ini tersedia untuk diujiankan.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama Mata Pelajaran'),
                IconEntry::make('is_active')
                    ->label('Tersedia')
                    ->boolean(),
                TextEntry::make('description')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d F Y, H:i:s')
                    ->visible(fn (Subject $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d F Y, H:i:s')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d F Y: H:i:s')
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at','desc')
            ->columns([
                TextColumn::make('No')
                    ->rowIndex()
                    ->width(40),
                TextColumn::make('name')
                    ->label('Pelajaran')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Tersedia')
                    ->boolean(),
                TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->counts('questions')
                    ->badge(),
                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Ditambah')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Pelajaran')
                    ->placeholder('Pilih Salah Satu')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia')
                    ->native(false),
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Soal')
                    ->url(fn (Subject $record): string => QuestionResource::getUrl('index', [
                        'filters' => [
                            'subject_id' => ['value' => $record->id],
                        ],
                    ])),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubjects::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}