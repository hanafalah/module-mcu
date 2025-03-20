<?php

namespace Aibnuhibban\ModuleMcu\Resources\McuCategory;

use Zahzah\LaravelSupport\Resources\ApiResource;

class McuCategory extends ApiResource
{
    public function toArray(\Illuminate\Http\Request $request): array
    {
        $arr = [];

        $arr = array_merge(parent::toArray($request), $arr);
        
        return $arr;
    }
}
