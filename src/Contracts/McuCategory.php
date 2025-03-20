<?php

namespace Hanafalah\ModuleMcu\Contracts;

use Hanafalah\LaravelSupport\Contracts\DataManagement;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface McuCategory extends DataManagement
{
    public function mcuCategory(mixed $conditionals = null): Builder;
    public function getMcuCategories();
    public function addOrChange(?array $attributes = []): self;
}
