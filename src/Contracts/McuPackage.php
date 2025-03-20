<?php

namespace Aibnuhibban\ModuleMcu\Contracts;

use Zahzah\LaravelSupport\Contracts\DataManagement;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface McuPackage extends DataManagement{
    public function showUsingRelation(): array;
    public function prepareViewMcuPackageList(? array $attributes = null): Collection;
    public function viewMcuPackageList(): array ;
    public function prepareViewMcuPackagePaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page',? int $page = null,? int $total = null): LengthAwarePaginator;
    public function viewMcuPackagePaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page',? int $page = null,? int $total = null): array;
    public function getMcuPackage(): mixed;
    public function prepareShowMcuPackage(? Model $model = null,? array $attributes = null): Model;
    public function showMcuPackage(? Model $model = null);
    public function prepareStoreMcuPackage(? Model $model = null,? array $attributes = null): Model;
    public function storeMcuPackage(): array;
    public function mcuPackage(mixed $conditionals = null): Builder;
    public function prepareDeleteMcuPackage(? array $attributes = null): bool;
    public function deleteMcuPackage(): bool;
}
