<?php

namespace Aibnuhibban\ModuleMcu\Schemas;

use Aibnuhibban\ModuleMcu\Enums\McuPackage\Flag;
use Aibnuhibban\ModuleMcu\Contracts\McuPackage as ContractsMcuPackage;
use Aibnuhibban\ModuleMcu\Resources\McuPackage\ShowMcuPackage;
use Aibnuhibban\ModuleMcu\Resources\McuPackage\ViewMcuPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Support\Str;

class McuPackage extends PackageManagement implements ContractsMcuPackage
{

    protected string $__entity = 'McuPackage';
    public static $mcu_package_model;

    protected array $__resources = [
        'view' => ViewMcuPackage::class,
        'show' => ShowMcuPackage::class
    ];

    public function showUsingRelation(): array
    {
        return [
            'treatment' => function ($query) {
                $query->with('serviceItems');
            },
            'company',
            'treatments' => function ($query) {
                $query->with(['servicePrices' => function ($query) {
                    $query->with('serviceItem');
                }])->orderBy('props->order', 'asc');
            }
        ];
    }

    public function prepareViewMcuPackageList(?array $attributes = null): Collection
    {
        $attributes ??= request()->all();

        $model = $this->mcuPackage()->get();
        return static::$mcu_package_model = $model;
    }

    public function viewMcuPackageList(): array
    {
        return $this->transforming($this->__resources['view'], function () {
            return $this->prepareViewMcuPackageList();
        });
    }

    public function prepareViewMcuPackagePaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): LengthAwarePaginator
    {
        $paginate_options = compact('perPage', 'columns', 'pageName', 'page', 'total');
        return static::$mcu_package_model = $this->mcuPackage()->paginate(...$this->arrayValues($paginate_options))->appends(request()->all());
    }

    public function viewMcuPackagePaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): array
    {
        $paginate_options = compact('perPage', 'columns', 'pageName', 'page', 'total');
        return $this->transforming($this->__resources['view'], function () use ($paginate_options) {
            return $this->prepareViewMcuPackagePaginate(...$this->arrayValues($paginate_options));
        });
    }

    public function getMcuPackage(): mixed
    {
        return static::$mcu_package_model;
    }

    public function prepareShowMcuPackage(?Model $model = null, ?array $attributes = null): Model
    {
        $attributes ??= request()->all();

        $model ??= $this->getMcuPackage();
        if (!isset($model)) {
            $id = $attributes['id'] ?? null;
            if (!isset($id)) throw new \Exception('id not found');

            $model = $this->mcuPackage()->with($this->showUsingRelation())->find($id);
        } else {
            $model->load($this->showUsingRelation());
        }

        return static::$mcu_package_model = $model;
    }

    public function showMcuPackage(?Model $model = null)
    {
        return $this->transforming($this->__resources['show'], function () use ($model) {
            return $this->prepareShowMcuPackage($model);
        });
    }

    public function prepareStoreMcuPackage(?Model $model = null, ?array $attributes = null): Model
    {
        $attributes ??= request()->all();

        if (!isset($attributes['medic_services'])) throw new \Exception('medic_services not found');

        $model = $this->mcuPackage()->updateOrCreate([
            'id'        => $attributes['id'] ?? null,
            'parent_id' => $attributes['parent_id'] ?? null
        ], [
            'name'      => $attributes['name'],
            'flag'      => $attributes['flag'] ?? Flag::MAIN_PACKAGE->value
        ]);

        $model->acronym         = $attributes['acronym'] ?? null;
        $model->is_food_handler = $attributes['is_food_handler'] ?? false;
        $treatment = $model->treatment;
        if (!isset($treatment)) throw new \Exception('treatment not found');

        if (isset($attributes['boats'])) {
            $this->hasOrganization($model, $treatment, 'boat', $attributes['boats']);
        }

        if (isset($attributes['companies'])) {
            $this->hasOrganization($model, $treatment, 'company', $attributes['companies']);
        }

        if (isset($attributes['payers'])) {
            $this->hasOrganization($model, $treatment, 'payer', $attributes['payers']);
        }

        if (isset($attributes['agents'])) {
            $this->hasOrganization($model, $treatment, 'agent', $attributes['agents']);
        }
        $service_item_schema = $this->schemaContract('service_item');
        $keep_service_item   = [];
        $total_treatment     = 0;
        $total_polyclinic    = 0;
        $poly_arr            = [];
        $treatment_arr       = [];

        $prop_medic_services = [];
        foreach ($attributes['medic_services'] as $medic_service) {
            $service_prop = [
                'id'       => $medic_service['id'],
                'services' => []
            ];

            $poly_clinic = $this->ServiceModel()->findOrFail($medic_service['id']);
            if (!isset($medic_service['id'])) throw new \Exception('service_id not found');

            $service_item = $service_item_schema->prepareStoreServiceItem([
                'service_id'      => $treatment->getKey(),
                'reference_id'    => $poly_clinic->getKey(),
                'reference_type'  => $poly_clinic->getMorphClass(),
                'flag'            => 'POLYCLINIC',
                'treatment_price' => $attributes['price'] ?? null
            ]);
            $poly_arr[] = $service_item;

            $keep_service_item[] = $service_item->getKey();
            foreach ($medic_service['services'] as $service_id) {
                $service_prop['services'][] = $service_id;
                $treatment_list = $this->TreatmentModel()->findOrFail($service_id);
                $item_service_item = $service_item_schema->prepareStoreServiceItem([
                    'parent_id'       => $service_item->getKey(),
                    'service_id'      => $treatment->getKey(),
                    'reference_id'    => $treatment_list->getKey(),
                    'reference_type'  => $treatment_list->getMorphClass(),
                    'flag'            => 'ITEM',
                    'treatment_price' => $attributes['price'] ?? null
                ]);
                $total_treatment    += $treatment_list->price ?? 0;
                $total_polyclinic   += $total_treatment;
                $treatment_arr[]     = $item_service_item;
                $keep_service_item[] = $item_service_item->getKey();
            }
            $prop_medic_services[] = $service_prop;
        }
        $model->setAttribute('prop_medic_services', $prop_medic_services);

        $treatment->price            = $attributes['price'] ?? $total_treatment ?? 0;
        $treatment->acronym          = $attributes['acronym'] ?? null;
        $treatment->is_food_handler  = $attributes['is_food_handler'] ?? false;
        $treatment->order            = 1;
        if (isset($attributes['mcu_category_id'])) {
            $treatment->modelHasService()->where('reference_type', $this->McuCategoryModel()->getMorphClass())->delete();
            $mcu_category = $this->McuCategoryModel()->findOrFail($attributes['mcu_category_id']);
            $treatment->modelHasService()->firstOrCreate([
                'reference_id'   => $treatment->getKey(),
                'reference_type' => $treatment->getMorphClass(),
                'service_id'     => $attributes['mcu_category_id']
            ]);
            $treatment->mcu_category = [
                'id'    => $attributes['mcu_category_id'],
                'name'  => $mcu_category->name
            ];
            $model->mcu_category = $treatment->mcu_category;
        }
        $treatment->save();
        $model->save();

        foreach ($poly_arr as $poly_item) {
            $service_price = $poly_item->servicePrice;
            $service_price->percentage = $service_price->price == 0 ? 0 : ($service_price->price / $total_polyclinic) * 100;
            if (isset($attributes['price'])) $service_price->price = intval($attributes['price'] * $service_price->percentage / 100);
            $service_price->save();
        }

        $new_total_treatment = 0;
        foreach ($treatment_arr as $treatment_item) {
            $service_price = $treatment_item->servicePrice;
            $service_price->percentage = $service_price->price == 0 ? 0 : ($service_price->price / $total_treatment) * 100;
            if (isset($attributes['price'])) $service_price->price = intval($attributes['price'] * $service_price->percentage / 100);
            $new_total_treatment += $service_price->price ?? 0;
            $service_price->save();
        }

        $new_total_treatment = intval($new_total_treatment);
        if ($new_total_treatment !== $treatment->price) {
            $treatment_price = end($treatment_arr);
            $treatment_price = $treatment_price->servicePrice;
            $treatment_price->refresh();
            $diff            = $new_total_treatment - $treatment->price;
            $treatment_price->price -= $diff;
            $treatment_price->save();
        }
        $service_items = $this->ServiceItemModel()->where('service_id', $treatment->getKey())->whereNotIn('id', $keep_service_item)->get();
        foreach ($service_items as $service_item) {
            $service_item->delete();
        }
        return static::$mcu_package_model = $model;
    }

    protected function hasOrganization(&$model, &$treatment, $organization_type, mixed $id)
    {
        $ids = $this->mustArray($id);
        $prop_organization  = [];
        $organization_names = [];
        $organization_ids   = [];
        foreach ($ids as $id) {
            $organization = $this->{\ucfirst($organization_type) . 'Model'}()->findOrFail($id);

            $model->{$organization_type . 'HasOrganization'}()->firstOrCreate([
                'organization_id'   => $organization->getKey(),
                'organization_type' => $organization->getMorphClass()
            ]);

            $prop_organization[] = [
                $organization->getKeyName() => $organization->getKey(),
                'name' => $organization->name
            ];
            $organization_names[] = $organization->name;
            $organization_ids[]   = $organization->getKey();
        }
        $organization_names = implode(',', $organization_names);
        $organization_ids   = implode(',', $organization_ids);
        $model->setAttribute($organization_type . '_names', $organization_names);
        $model->setAttribute($organization_type . '_ids', $organization_ids);
        $treatment->setAttribute($organization_type . '_ids', $organization_ids);

        $model->setAttribute('prop_' . Str::plural($organization_type), $prop_organization);
    }

    public function storeMcuPackage(): array
    {
        return $this->transaction(function () {
            return $this->showMcuPackage($this->prepareStoreMcuPackage());
        });
    }

    public function prepareDeleteMcuPackage(?array $attributes = null): bool
    {
        $attributes ??= request()->all();

        if (!isset($attributes['id'])) throw new \Exception('id not found');

        $model = $this->mcuPackage()->findOrFail($attributes['id']);
        return $model->delete();
    }

    public function deleteMcuPackage(): bool
    {
        return $this->transaction(function () {
            return $this->prepareDeleteMcuPackage();
        });
    }

    public function mcuPackage(mixed $conditionals = null): Builder
    {
        $this->booting();

        return $this->McuPackageModel()->with([
            'treatments.servicePrices',
            'treatment.serviceItems' => function ($query) {
                $query->with(['servicePrice', 'childs.servicePrice']);
            },
        ])->conditionals($conditionals)->withParameters()
            ->orderBy('name', 'asc');
    }
}
