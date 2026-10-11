<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use MHStudio\FilamentBookingEngine\Booking\BookingManager;
use MHStudio\FilamentBookingEngine\Booking\BookingRequest;
use MHStudio\FilamentBookingEngine\Data\Slot;
use MHStudio\FilamentBookingEngine\Engine\AvailabilityEngine;
use MHStudio\FilamentBookingEngine\Exceptions\SlotUnavailableException;
use MHStudio\FilamentBookingEngine\Models\Provider;
use MHStudio\FilamentBookingEngine\Models\Service;
use MHStudio\FilamentBookingEngine\Schedule\ScheduleResolver;

/** Public booking wizard. */
#[Layout('filament-booking-engine::layouts.public')]
final class BookingWizard extends Component
{
    public ?int $serviceId = null;

    public ?int $providerId = null;

    public ?string $date = null;

    public ?string $slotStart = null;

    public string $clientName = '';

    public string $clientEmail = '';

    public string $clientPhone = '';

    public string $notes = '';

    public string $timezone = 'UTC';

    public ?string $confirmationMessage = null;

    public function mount(?string $service = null): void
    {
        if ($service !== null) {
            $this->serviceId = Service::query()
                ->where('slug', $service)
                ->value('id');
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $services = config('booking-engine.tables.services', 'booking_services');
        $providers = config('booking-engine.tables.providers', 'booking_providers');

        return [
            'serviceId' => ['required', 'integer', "exists:{$services},id"],
            'providerId' => ['required', 'integer', "exists:{$providers},id"],
            'slotStart' => ['required', 'date'],
            'clientName' => ['required', 'string', 'max:255'],
            'clientEmail' => ['required', 'email', 'max:255'],
            'clientPhone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function updatedServiceId(): void
    {
        $this->reset('slotStart');
    }

    public function updatedProviderId(): void
    {
        $this->reset('slotStart');
    }

    public function updatedDate(): void
    {
        $this->reset('slotStart');
    }

    public function selectSlot(string $start): void
    {
        $this->slotStart = $start;
    }

    public function book(): void
    {
        $this->validate();

        $service = Service::query()->findOrFail($this->serviceId);
        $provider = Provider::query()->findOrFail($this->providerId);

        try {
            app(BookingManager::class)->create(new BookingRequest(
                service: $service,
                provider: $provider,
                start: CarbonImmutable::parse($this->slotStart),
                clientName: $this->clientName,
                clientEmail: $this->clientEmail,
                clientPhone: $this->clientPhone,
                notes: $this->notes,
                timezone: $this->timezone,
            ));
        } catch (SlotUnavailableException $exception) {
            $this->addError('slotStart', 'That slot was just taken. Please choose another.');
            $this->reset('slotStart');

            return;
        }

        $this->confirmationMessage = 'Your appointment is booked. A confirmation has been sent.';
        $this->reset('slotStart', 'clientName', 'clientEmail', 'clientPhone', 'notes');
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServicesProperty(): Collection
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Provider>
     */
    public function getProvidersProperty(): Collection
    {
        return Provider::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function getProviderModelProperty(): ?Provider
    {
        return $this->providerId ? Provider::query()->find($this->providerId) : null;
    }

    /**
     * @return Slot[]
     */
    public function getSlotsProperty(): array
    {
        if (! $this->serviceId || ! $this->providerId || ! $this->date) {
            return [];
        }

        $service = Service::query()->find($this->serviceId);
        $provider = Provider::query()->find($this->providerId);

        if (! $service || ! $provider) {
            return [];
        }

        $day = CarbonImmutable::parse($this->date, $provider->bookingTimezone());

        $context = app(ScheduleResolver::class)->contextFor($service, $provider, $day);

        return app(AvailabilityEngine::class)->availableSlots($context);
    }

    public function view(): string
    {
        return 'filament-booking-engine::livewire.booking-wizard';
    }
}
