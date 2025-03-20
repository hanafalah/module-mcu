<?php

namespace Hanafalah\ModuleMcu\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelSupport\Contracts\DataManagement;

interface McuServicePrice extends DataManagement
{
    public function showUsingRelation(): array;
    public function prepareViewMcuServicePriceList(?array $attributes = null): Collection;
    public function viewMcuServicePriceList(): array;
    public function getMcuServicePrice(): mixed;
    public function prepareShowMcuServicePrice(?Model $model = null, ?array $attributes = null): Model;
    public function showMcuServicePrice(?Model $model = null);
    public function prepareStoreMcuServicePrice(?array $attributes = null): Model;
    public function storeMcuServicePrice(): array;
    public function prepareStoreMultipleServicePrice(?array $attributes = null): Model;
    public function storeMultipleMcuServicePrice(): array;
    public function prepareDeleteMcuServicePrice(?array $attributes = null): bool;
    public function deleteMcuServicePrice(): bool;
    public function mcuServicePrice(mixed $conditionals = null): Builder;
}
