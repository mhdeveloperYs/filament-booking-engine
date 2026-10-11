<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages;

use Filament\Resources\Pages\ListRecords;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\ProviderResource;

final class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;
}
