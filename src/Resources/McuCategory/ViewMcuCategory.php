<?php

namespace Aibnuhibban\ModuleMcu\Resources\McuCategory;

use Zahzah\LaravelSupport\Resources\ApiResource;

class ViewMcuCategory extends ApiResource
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
            "id"                 => $this->id,
            "name"               => $this->name,
        ];
        
        return $arr;
    }
}
