<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages;

use Filament\Resources\Pages\CreateRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\ProviderResource;

final class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;
}
