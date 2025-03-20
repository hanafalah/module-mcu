<?php

namespace Hanafalah\ModuleMcu\Resources\McuCategory;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class McuCategory extends ApiResource
{
    public function toArray(\Illuminate\Http\Request $request): array
    {
        $arr = [];

        $arr = array_merge(parent::toArray($request), $arr);

        return $arr;
    }
}
