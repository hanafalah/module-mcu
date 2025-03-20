<?php

namespace Hanafalah\ModuleMcu\Models\McuServicePrice;

use Hanafalah\ModuleMcu\Resources\McuServicePrice\ShowMcuServicePrice;
use Hanafalah\ModuleMcu\Resources\McuServicePrice\ViewMcuServicePrice;
use Gilanggustina\ModuleTreatment\Models\Treatment\Treatment;

class McuServicePrice extends Treatment
{
    protected $table = 'services';

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('mcu_reference', function ($query) {
            $query->where($query->getModel()->getTableName() . '.reference_type', app(config('database.models.McuPackage'))->getMorphClass());
        });
    }

    public function toViewApi()
    {
        return new ViewMcuServicePrice($this);
    }

    public function toShowApi()
    {
        return new ShowMcuServicePrice($this);
    }

    public function transaction()
    {
        return $this->morphOneModel('Transaction', 'reference');
    }
}
