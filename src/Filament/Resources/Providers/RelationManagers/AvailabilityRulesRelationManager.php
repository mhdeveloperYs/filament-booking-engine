<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MHStudio\FilamentBookingEngine\Enums\Weekday;

final class AvailabilityRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityRules';

    protected static ?string $title = 'Weekly availability';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('weekday')
                    ->options(collect(Weekday::cases())->mapWithKeys(
                        fn (Weekday $weekday): array => [$weekday->value => $weekday->label()],
                    ))
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
                TimePicker::make('start_time')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->seconds(false)
                    ->required(),
                TextInput::make('slot_interval_minutes')
                    ->label('Slot interval')
                    ->numeric()
                    ->suffix('min')
                    ->helperText('Overrides the service / package interval for this window.'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('weekday')
                    ->formatStateUsing(fn (Weekday $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('slot_interval_minutes')
                    ->label('Interval')
                    ->suffix(' min')
                    ->placeholder('default'),
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
            ->defaultSort('weekday');
    }
}
