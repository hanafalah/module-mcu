<?php

namespace Aibnuhibban\ModuleMcu\Schemas;

use Aibnuhibban\ModuleMcu\Contracts\McuServicePrice as ContractsMcuServicePrice;
use Aibnuhibban\ModuleMcu\Resources\McuServicePrice\ShowMcuServicePrice;
use Aibnuhibban\ModuleMcu\Resources\McuServicePrice\ViewMcuServicePrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Zahzah\LaravelSupport\Supports\PackageManagement;

class McuServicePrice extends PackageManagement implements ContractsMcuServicePrice {
    
    protected string $__entity = 'McuServicePrice';
    public static $mcu_service_price_model;

    protected array $__resources = [
        'view' => ViewMcuServicePrice::class,
        'show' => ShowMcuServicePrice::class
    ];

    public function showUsingRelation(): array {
        return [
            'reference',
            'servicePrices' => function($query){
                $query->with('serviceItem');
            }
        ];
    }

    public function prepareViewMcuServicePriceList(? array $attributes = null): Collection{
        $attributes ??= request()->all();

        $model = $this->mcuServicePrice()->when(isset($attributes['mcu_package_id']),function($query) use ($attributes){
            $query->where('reference_id',$attributes['mcu_package_id'])
                  ->where('reference_type',$this->McuPackageModel()->getMorphClass());
        })->get();
        return static::$mcu_service_price_model = $model;
    }

    public function viewMcuServicePriceList(): array {
        return $this->transforming($this->__resources['view'],function(){
            return $this->prepareViewMcuServicePriceList();
        });
    }

    public function getMcuServicePrice(): mixed{
        return static::$mcu_service_price_model;
    }

    public function prepareShowMcuServicePrice(? Model $model = null,? array $attributes = null): Model{
        $attributes ??= request()->all();

        $model ??= $this->getMcuServicePrice();
        if (!isset($model)){
            $id = $attributes['id'] ?? null;
            if (!isset($id)) throw new \Exception('id not found');

            $model = $this->mcuServicePrice()->with($this->showUsingRelation())->find($id);
        }else{
            $model->load($this->showUsingRelation());
        }

        return static::$mcu_service_price_model = $model;
    }

    public function showMcuServicePrice(? Model $model = null){
        return $this->transforming($this->__resources['show'],function() use ($model){
            return $this->prepareShowMcuServicePrice($model);
        });
    }

    public function prepareStoreMcuServicePrice(? array $attributes = null): Model{
        $attributes ??= request()->all();
        $mcu_package = $this->McuPackageModel()->findOrFail($attributes['mcu_package_id']);
        $service     = $mcu_package->treatment()->whereNull('parent_id')->firstOrFail();
        if (isset($attributes['id'])){
            $mcu_service_package = $this->mcuServicePrice()->findOrFail($attributes['id']);
        }else{
            $mcu_service_package = $this->McuServicePriceModel()->create([
                'parent_id'      => $service->getKey(),
                'reference_id'   => $service->reference_id,
                'reference_type' => $service->reference_type,
                'name'           => $attributes['name'],
                'status'         => $service->status
            ]);
        }
        $props = $service->getPropsKey();
        $mcu_service_package->order = 2;
        foreach ($props as $key => $prop) {
            if ($key == 'price') continue;
            $mcu_service_package->{$key} = $prop;
        }        

        $mcu_service_package->acronym              = $attributes['acronym'] ?? null;
        $mcu_service_package->mcu_category_id      = $attributes['mcu_category_id'] ?? null;
        if (isset($attributes['mcu_category_id'])){
            $mcu_service_package->modelHasService()->where('reference_type',$this->McuCategoryModel()->getMorphClass())->delete();
            $mcu_category = $this->McuCategoryModel()->findOrFail($attributes['mcu_category_id']);
            $mcu_category_service = $mcu_service_package->modelHasService()->firstOrCreate([
                'reference_id'   => $mcu_service_package->getKey(),
                'reference_type' => $mcu_service_package->getMorphClass(),
                'service_id'     => $attributes['mcu_category_id']
            ]);
            $mcu_service_package->mcu_category = [
                'id'    => $attributes['mcu_category_id'],
                'name'  => $mcu_category->name
            ];
        }

        $guarantor_categories = $this->mergeArray([
            'agent'   => null,
            'payer'   => null,
            'company' => null
        ],$attributes['guarantor_categories'] ?? []);

        if (isset($attributes['service_category'])){
            $service_category = $this->ServiceModel()->findOrFail($attributes['service_category']);
            $mcu_service_package->service_category = [
                'id'   => $service_category->getKey(),
                'name' => $service_category->name
            ];
        }
        if (isset($attributes['guarantor_categories'])){
            foreach ($attributes['guarantor_categories'] as $key => $guarantor) {
                if ($key == 'agent' && isset($guarantor)){
                    $agent = $this->AgentModel()->findOrFail($guarantor);
                    $mcu_service_package->sync($agent,['id','name']);
                    $guarantor_categories['agent'] = [
                        'id'   => $agent->getKey(),
                        'name' => $agent->name
                    ];
                }
                if ($key == 'payer' && isset($guarantor)){
                    $payer = $this->PayerModel()->findOrFail($guarantor);
                    $mcu_service_package->sync($payer,['id','name']);
                    $guarantor_categories['payer'] = [
                        'id'   => $payer->getKey(),
                        'name' => $payer->name
                    ];
                }
                if ($key == 'company' && isset($guarantor)){
                    $company = $this->CompanyModel()->findOrFail($guarantor);
                    $mcu_service_package->sync($company,['id','name']);
                    $guarantor_categories['company'] = [
                        'id'   => $company->getKey(),
                        'name' => $company->name
                    ];
                }
            }
            if (isset($mcu_service_package->payer) && !isset($mcu_service_package->company)){
                $company = $this->CompanyModel()->findOrFail($mcu_service_package->payer['id']);
                $mcu_service_package->sync($company,['id','name']);
            }
            $mcu_service_package->setAttribute('guarantor_categories',$guarantor_categories);
        }

        $total_price = 0;

        $service_price_schema = $this->schemaContract('service_price');
        foreach ($attributes['service_prices'] as $attribute_price) {
            $parent = $this->ServicePriceModel()->where('service_id',$service->getKey())
                           ->when(isset($attribute_price['parent_id']),function($query) use ($attribute_price){
                               return $query->where('id',$attribute_price['parent_id']);
                           })
                           ->when(isset($attribute_price['service_item_id']),function($query) use ($attribute_price){
                               return $query->where('service_item_id',$attribute_price['service_item_id'])
                                            ->where('service_item_type',$attribute_price['service_item_type']);
                           })->firstOrFail();
            if ($service->getKey() != $mcu_service_package->getKey()){
                $attribute_price['parent_id'] = $parent->getKey();
            }

            $attribute_price['service_id']        = $mcu_service_package->getKey();
            $attribute_price['service_item_id']   = $parent->service_item_id;
            $attribute_price['service_item_type'] = $parent->service_item_type;

            $attribute_price['price'] = $attribute_price['price'] ?? 0;
            $total_price += $attribute_price['price'];
            $service_price = $service_price_schema->prepareStoreServicePrice($attribute_price);            
        }

        $mcu_service_package->price = $attributes['price'] ?? $total_price;
        $mcu_service_package->save();
        return static::$mcu_service_price_model = $mcu_service_package;
    }

    public function storeMcuServicePrice(): array{
        return $this->transaction(function(){
            return $this->showMcuServicePrice($this->prepareStoreMcuServicePrice());
        });
    }

    public function prepareStoreMultipleServicePrice(? array $attributes = null): Model{
        $attributes ??= request()->all();
        foreach ($attributes['treatments'] as $key => $service) {
            $service['mcu_package_id'] = $attributes['mcu_package_id'];
            $this->prepareStoreMcuServicePrice($service);
        }
        $mcu_package = $this->McuPackageModel()->with('treatments.servicePrices')->findOrFail($attributes['mcu_package_id']);
        return $mcu_package;
    }

    public function storeMultipleMcuServicePrice(): array{
        return $this->transaction(function(){
            $mcu_package_schema = $this->schemaContract('mcu_package');
            return $mcu_package_schema->showMcuPackage($this->prepareStoreMultipleServicePrice());
        });
    }

    public function prepareDeleteMcuServicePrice(? array $attributes = null): bool{
        $attributes ??= request()->all();

        if (!isset($attributes['id'])) throw new \Exception('id not found');

        $model = $this->mcuServicePrice()->findOrFail($attributes['id']);
        return $model->delete();
    }

    public function deleteMcuServicePrice(): bool{
        return $this->transaction(function(){
            return $this->prepareDeleteMcuServicePrice();
        });
    }

    public function mcuServicePrice(mixed $conditionals = null): Builder{
        $this->booting();
        return $this->McuServicePriceModel()->with($this->showUsingRelation())
                    ->conditionals($conditionals)->withParameters()
                    ->orderBy('props->order','asc')
                    ->orderBy('name','asc');
    }
}
