<?php

namespace Aibnuhibban\ModuleMcu\Resources\McuPackage;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewMcuPackage extends ApiResource
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
            'id'               => $this->id,
            'parent_id'        => $this->parent_id,
            'name'             => $this->name,
            'flag'             => $this->flag,
            'company_id'       => $this->company_id,
            'agent_id'         => $this->agent_id,
            'payer_id'         => $this->payer_id,
            'company'          => isset($this->prop_company) ? \call_user_func(function () {
                return [
                    'id' => $this->prop_company['id'],
                    'name' => $this->prop_company['name']
                ];
            }) : null,

            'companies'      => $this->prop_companies ?? [],
            'payers'         => $this->prop_payers ?? [],
            'agents'         => $this->prop_agents ?? [],
            'medic_services' => $this->prop_medic_services ?? [],
            'treatment'      => $this->relationValidation('treatment', function () {
                return $this->treatment->toViewApi();
            }),
            'treatments'       => $this->relationValidation('treatments', function () {
                return $this->treatments->transform(function ($treatment) {
                    return $treatment->toViewApi();
                });
            }),
            'price_components' => $this->relationValidation('priceComponents', function () {
                return $this->priceComponents->transform(function ($priceComponent) {
                    return $priceComponent->toViewApi();
                });
            }),
            'childs' => $this->relationValidation('childs', function () {
                return $this->childs->transform(function ($child) {
                    return $child->toViewApi();
                });
            })
        ];

        return $arr;
    }
}
