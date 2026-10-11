<?php

declare(strict_types=1);

namespace MHStudio\FilamentBookingEngine\Filament\Resources\Providers\Pages;

use Filament\Resources\Pages\EditRecord;
use MHStudio\FilamentBookingEngine\Filament\Resources\Providers\ProviderResource;

final class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;
}
