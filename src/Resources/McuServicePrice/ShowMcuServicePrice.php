<?php

namespace Aibnuhibban\ModuleMcu\Resources\McuServicePrice;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class ShowMcuServicePrice extends ViewMcuServicePrice
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        $arr = [
            'childs'    => $this->relationValidation('childs', function () {
                return $this->childs->transform(function ($child) {
                    return $child->toShowApi();
                });
            }),
            'service_prices' => $this->relationValidation('servicePrices', function () {
                return $this->servicePrices->transform(function ($service_price) {
                    return $service_price->toShowApi();
                });
            })
        ];
        $arr = $this->mergeArray(parent::toArray($request), $arr);

        return $arr;
    }
}
