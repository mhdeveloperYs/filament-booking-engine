<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AvailabilityExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityExceptions';

    protected static ?string $title = 'Date exceptions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->required(),
            Toggle::make('is_closed')
                ->label('Closed all day')
                ->default(true)
                ->live(),
            TextInput::make('reason')
                ->maxLength(255),
            Repeater::make('windows')
                ->label('Custom opening windows')
                ->visible(fn (callable $get): bool => ! $get('is_closed'))
                ->schema([
                    TimePicker::make('start')
                        ->seconds(false)
                        ->required(),
                    TimePicker::make('end')
                        ->seconds(false)
                        ->required(),
                ])
                ->columns(2)
                ->addActionLabel('Add window')
                ->defaultItems(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_closed')
                    ->label('Closed')
                    ->boolean(),
                TextColumn::make('reason')
                    ->toggleable(),
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
            ->defaultSort('date', 'desc');
    }
}
