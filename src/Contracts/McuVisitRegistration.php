<?php

namespace Aibnuhibban\ModuleMcu\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Zahzah\ModulePatient\Contracts\VisitRegistration;

interface McuVisitRegistration extends VisitRegistration {
    public function addOrChange(? array $attributes=[]): self;
    public function prepareStoreMcuVisitRegistration(? array $attributes = null): Model;
    public function storeMcuVisitRegistration(): array;
    public function mcuVisitRegistration(mixed $conditionals = null): Builder;
}