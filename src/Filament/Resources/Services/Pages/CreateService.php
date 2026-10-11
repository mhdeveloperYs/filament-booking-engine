<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages;

use Filament\Resources\Pages\CreateRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\ServiceResource;

final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;
}
