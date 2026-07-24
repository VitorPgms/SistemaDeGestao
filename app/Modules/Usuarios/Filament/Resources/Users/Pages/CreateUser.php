<?php

namespace App\Modules\Usuarios\Filament\Resources\Users\Pages;

use App\Modules\Usuarios\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
