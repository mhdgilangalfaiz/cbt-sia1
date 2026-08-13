<?php

namespace App\Filament\Resources\Migrations;

use App\Filament\Resources\Migrations\Pages\ManageMigrations;
use App\Models\Migration;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MigrationResource extends Resource
{
    protected static ?string $model = Migration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $recordTitleAttribute = 'migration';

    protected static string|UnitEnum|null $navigationGroup = 'Basic Data';

    protected static ?string $modelLabel = 'Migration';

    protected static ?string $pluralModelLabel = 'Migrations';

    protected static ?string $navigationLabel = 'Data Migration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('migration')
                    ->label('Nama File Migration')
                    ->placeholder('contoh: 2026_07_29_085611_create_answers_table')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('batch')
                    ->label('Batch')
                    ->helperText('Menandakan migration ini dijalankan pada urutan/kelompok eksekusi ke berapa.')
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('migration')
                    ->label('Nama File Migration')
                    ->columnSpanFull(),
                TextEntry::make('batch')
                    ->label('Batch'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('migration')
            ->defaultSort('id', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->sortable(),
                TextColumn::make('migration')
                    ->label('Nama File Migration')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('batch')
                    ->label('Batch')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMigrations::route('/'),
        ];
    }
}