<div class="mx-auto max-w-2xl space-y-6 p-6">
    @if ($confirmationMessage)
        <div class="rounded-lg bg-green-50 p-4 text-green-800">
            {{ $confirmationMessage }}
        </div>
    @else
        <div class="space-y-4">
            {{-- Service --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Service</label>
                <select wire:model.live="serviceId" class="mt-1 w-full rounded border-gray-300">
                    <option value="">Choose a service…</option>
                    @foreach ($this->services as $service)
                        <option value="{{ $service->id }}">
                            {{ $service->name }} ({{ $service->duration_minutes }} min)
                        </option>
                    @endforeach
                </select>
                @error('serviceId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Provider --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Provider</label>
                <select wire:model.live="providerId" class="mt-1 w-full rounded border-gray-300">
                    <option value="">Choose a provider…</option>
                    @foreach ($this->providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
                @error('providerId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" wire:model.live="date" class="mt-1 w-full rounded border-gray-300">
            </div>

            {{-- Slots --}}
            @if ($this->slots)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Available times</label>
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        @foreach ($this->slots as $slot)
                            @php($iso = $slot->start()->toIso8601String())
                            <button
                                type="button"
                                wire:click="selectSlot('{{ $iso }}')"
                                class="rounded border px-2 py-1 text-sm {{ $slotStart === $iso ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-800' }}"
                            >
                                {{ $slot->start()->setTimezone($this->providerModel?->bookingTimezone() ?? 'UTC')->format('H:i') }}
                            </button>
                        @endforeach
                    </div>
                    @error('slotStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($serviceId && $providerId && $date)
                <p class="text-sm text-gray-500">No times available on this date.</p>
            @endif

            {{-- Details --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model="clientName" class="mt-1 w-full rounded border-gray-300">
                    @error('clientName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model="clientEmail" class="mt-1 w-full rounded border-gray-300">
                    @error('clientEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="tel" wire:model="clientPhone" class="mt-1 w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <input type="text" wire:model="notes" class="mt-1 w-full rounded border-gray-300">
                </div>
            </div>

            <button
                type="button"
                wire:click="book"
                class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
            >
                Book appointment
            </button>
        </div>
    @endif
</div>
