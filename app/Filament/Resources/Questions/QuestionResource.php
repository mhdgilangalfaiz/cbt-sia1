<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Filament\Resources\Questions\Pages\ViewQuestion;
use App\Models\Question;
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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $recordTitleAttribute = 'payload';

    protected static string|UnitEnum|null $navigationGroup = 'Bank Soal';

    protected static ?string $modelLabel = 'Soal';

    protected static ?string $pluralModelLabel = 'Soal';

    protected static ?string $navigationLabel = 'Data Soal';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->columnSpanFull(),

                Textarea::make('payload')
                    ->label('Pertanyaan')
                    ->placeholder('Tuliskan teks soal di sini...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('score')
                    ->label('Skor')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false)
                    ->helperText(
                        fn ($state) => $state
                            ? 'Soal ini akan muncul saat ujian berlangsung.'
                            : 'Soal ini disembunyikan dan tidak akan muncul saat ujian.'
                    ),

                Textarea::make('description')
                    ->label('Catatan / Pembahasan')
                    ->placeholder('Opsional, misalnya penjelasan jawaban')
                    ->rows(2)
                    ->columnSpanFull(),

                Repeater::make('answers')
                    ->label('Pilihan Jawaban')
                    ->relationship()
                    ->schema([
                        TextInput::make('text')
                            ->label('Teks Jawaban')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(3),

                        Toggle::make('is_correct')
                            ->label('Jawaban Benar')
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                                // Hanya boleh ada 1 jawaban benar dalam 1 soal.
                                // Begitu toggle ini dinyalakan, matikan semua toggle lain
                                // di repeater 'answers' yang sama (item saudara/sibling).
                                if (! $state) {
                                    return;
                                }

                                $siblings = $get('../') ?? [];

                                foreach (array_keys($siblings) as $key) {
                                    $set("../{$key}.is_correct", false);
                                }

                                $set('is_correct', true);
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(4)
                    ->addActionLabel('Tambah Pilihan Jawaban')
                    ->reorderable(false)
                    ->required()
                    ->helperText('Tandai tepat 1 opsi sebagai "Jawaban Benar". Minimal 2 pilihan jawaban.')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subject.name')
                    ->label('Mata Pelajaran'),
                TextEntry::make('score')
                    ->label('Skor'),
                IconEntry::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextEntry::make('payload')
                    ->label('Pertanyaan')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->label('Catatan / Pembahasan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                RepeatableEntry::make('answers_with_letter')
                    ->label('Pilihan Jawaban')
                    ->state(fn (Question $record) => $record->answers_with_letter)
                    ->schema([
                        TextEntry::make('letter')
                            ->label('')
                            ->weight('bold')
                            ->columnSpan(1),
                        TextEntry::make('text')
                            ->label('')
                            ->columnSpan(2),
                        IconEntry::make('is_correct')
                            ->label('')
                            ->boolean()
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                TextEntry::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d F Y, H:i:s')
                    ->visible(fn (Question $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d F Y, H:i:s'),
                TextEntry::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d F Y, H:i:s'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payload')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('No')
                    ->rowIndex()
                    ->width(40),
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payload')
                    ->label('Pertanyaan')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('answers_count')
                    ->label('Jml. Pilihan')
                    ->counts('answers')
                    ->badge(),
                TextColumn::make('score')
                    ->label('Skor')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
                SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Status Soal')
                    ->placeholder('Pilih Salah Satu')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->native(false),
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('answers')
            ->with('subject');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'view' => ViewQuestion::route('/{record}'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Question::count();
    }
}