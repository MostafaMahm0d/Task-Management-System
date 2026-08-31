<?php

namespace App\Filament\Tenant\Resources\Ratings\Pages;

use App\Filament\Tenant\Resources\Ratings\RatingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRating extends CreateRecord
{
    protected static string $resource = RatingResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['rater_id'] = auth()->id();

        return $data;
    }
}
