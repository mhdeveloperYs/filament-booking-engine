<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages;

use Filament\Resources\Pages\ListRecords;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\ServiceResource;

final class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;
}
