<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Services\Pages;

use Filament\Resources\Pages\EditRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Services\ServiceResource;

final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;
}
