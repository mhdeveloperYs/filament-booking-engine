<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Appointments;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Enums\AppointmentStatus;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages\CreateAppointment;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages\EditAppointment;
use MHStudio\FilamentBookingEngine\Filament\Resources\Appointments\Pages\ListAppointments;
use MHStudio\FilamentBookingEngine\Models\Appointment;
use UnitEnum;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appointment')->schema([
                Grid::make(2)->schema([
                    Select::make('service_id')
                        ->relationship('service', 'name')
                        ->required(),
                    Select::make('provider_id')
                        ->relationship('provider', 'name')
                        ->required(),
                    DateTimePicker::make('start_at')
                        ->required(),
                    Select::make('status')
                        ->options(AppointmentStatus::class)
                        ->default(AppointmentStatus::Confirmed)
                        ->required(),
                ]),
            ]),

            Section::make('Client')->schema([
                Grid::make(2)->schema([
                    TextInput::make('client_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('client_email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('client_phone')
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('timezone')
                        ->maxLength(64),
                ]),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable(),
                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->sortable(),
                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AppointmentStatus $state): string => $state->value)
                    ->color(fn (AppointmentStatus $state): string => match ($state) {
                        AppointmentStatus::Requested => 'warning',
                        AppointmentStatus::Confirmed => 'success',
                        AppointmentStatus::Cancelled => 'danger',
                        AppointmentStatus::Completed => 'info',
                        AppointmentStatus::NoShow => 'gray',
                    }),
                TextColumn::make('price')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AppointmentStatus::class),
                SelectFilter::make('provider_id')
                    ->relationship('provider', 'name')
                    ->label('Provider'),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record): bool => $record->status !== AppointmentStatus::Confirmed)
                    ->action(function (Appointment $record): void {
                        app(BookingManager::class)->confirm($record);

                        Notification::make()->title('Appointment confirmed')->success()->send();
                    }),
                Action::make('reschedule')
                    ->visible(fn (Appointment $record): bool => $record->status->isActive())
                    ->form([
                        DateTimePicker::make('start_at')
                            ->label('New start')
                            ->required(),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        try {
                            app(BookingManager::class)->reschedule(
                                $record,
                                CarbonImmutable::parse($data['start_at'], config('app.timezone')),
                            );

                            Notification::make()->title('Appointment rescheduled')->success()->send();
                        } catch (SlotUnavailableException $exception) {
                            Notification::make()
                                ->title('Slot unavailable')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record): bool => $record->status->isActive())
                    ->action(function (Appointment $record): void {
                        app(BookingManager::class)->cancel($record);

                        Notification::make()->title('Appointment cancelled')->success()->send();
                    }),
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
            ->defaultSort('start_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
