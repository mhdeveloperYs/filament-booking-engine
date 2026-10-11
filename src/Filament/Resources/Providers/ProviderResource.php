<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages\CreateProvider;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages\EditProvider;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages\ListProviders;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\RelationManagers\AvailabilityExceptionsRelationManager;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\RelationManagers\AvailabilityRulesRelationManager;
use MHStudio\FilamentBookingEngine\Models\Provider;
use UnitEnum;

final class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Provider')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('timezone')
                        ->maxLength(64)
                        ->helperText('IANA timezone (e.g. Africa/Casablanca). Availability clock faces are authored in this zone.')
                        ->placeholder((string) config('app.timezone', 'UTC')),
                    TextInput::make('color')
                        ->maxLength(20),
                ]),
                Toggle::make('is_active')
                    ->default(true),
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
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('timezone')
                    ->placeholder('App default')
                    ->toggleable(),
                TextColumn::make('availability_rules_count')
                    ->label('Rules')
                    ->counts('availabilityRules'),
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

    public static function getRelations(): array
    {
        return [
            AvailabilityRulesRelationManager::class,
            AvailabilityExceptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
