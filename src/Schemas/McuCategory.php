<?php

namespace Aibnuhibban\ModuleMcu\Schemas;

use Aibnuhibban\ModuleMcu\Contracts\McuCategory as ContractsMcuCategory;
use Illuminate\Database\Eloquent\Builder;
use Hanafalah\LaravelSupport\Supports\PackageManagement;

class McuCategory extends PackageManagement implements ContractsMcuCategory
{

    protected array $__guard   = ['id'];
    protected array $__add     = ['name'];
    protected string $__entity = 'McuCategory';
    public static $mcu_category_model;

    public function mcuCategory(mixed $conditionals = null): Builder
    {
        $this->booting();
        return $this->McuCategoryModel()->conditionals($conditionals);
    }

    public function getMcuCategories()
    {
        $datas =  $this->mcuCategory(function ($query) {
            if (request()->has('search_name')) {
                $query->where('name', 'like', '%' . request('search_name') . '%');
            }
        })->get();
        return $datas;
    }

    public function addOrChange(?array $attributes = []): self
    {
        $this->updateOrCreate($attributes);
        return $this;
    }
}
