<?php

namespace Aibnuhibban\ModuleMcu\Resources\McuServicePrice;

use Zahzah\LaravelSupport\Resources\ApiResource;

class ViewMcuServicePrice extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        $props = $this->getOriginal()['props'] ?? [];
        $arr = [
            'id'        => $this->id,
            'parent_id' => $this->parent_id,
            'childs'    => $this->relationValidation('childs',function(){
                return $this->childs->transform(function($child){
                    return $child->toViewApi();
                });
            }),
            'service_prices' => $this->relationValidation('servicePrices',function(){
                return $this->servicePrices->transform(function($service_price){
                    return $service_price->toViewApi();
                });
            })
        ];
        foreach ($props as $key => $prop) {
            $arr[$key] = $prop;
        }
        
        return $arr;
    }
}
