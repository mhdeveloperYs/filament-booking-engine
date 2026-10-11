<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Services;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages\CreateService;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages\EditService;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages\ListServices;
use MHStudio\FilamentBookingEngine\Models\Service;
use UnitEnum;

final class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Service')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]),

            Section::make('Timing')->schema([
                Grid::make(2)->schema([
                    TextInput::make('duration_minutes')
                        ->label('Duration')
                        ->numeric()
                        ->required()
                        ->default(30)
                        ->suffix('min'),
                    TextInput::make('slot_interval_minutes')
                        ->label('Slot interval')
                        ->numeric()
                        ->suffix('min')
                        ->helperText('Grid of candidate start times. Defaults to the package setting.'),
                    TextInput::make('buffer_before_minutes')
                        ->label('Buffer before')
                        ->numeric()
                        ->default(0)
                        ->suffix('min'),
                    TextInput::make('buffer_after_minutes')
                        ->label('Buffer after')
                        ->numeric()
                        ->default(0)
                        ->suffix('min'),
                ]),
            ]),

            Section::make('Pricing & status')->schema([
                Grid::make(2)->schema([
                    TextInput::make('price')
                        ->numeric()
                        ->prefix('$'),
                    TextInput::make('currency')
                        ->maxLength(3)
                        ->default('USD'),
                    TextInput::make('capacity')
                        ->numeric()
                        ->default(1),
                    Toggle::make('is_active')
                        ->default(true),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
                TextColumn::make('buffer_before_minutes')
                    ->label('Buffer before')
                    ->suffix(' min')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('buffer_after_minutes')
                    ->label('Buffer after')
                    ->suffix(' min')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
