<?php

namespace Aibnuhibban\ModuleMcu\Models\McuPackage;

use Aibnuhibban\ModuleMcu\Enums\McuPackage\Flag;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\LaravelSupport\Concerns\Support\HasEncoding;
use Hanafalah\LaravelSupport\Models\BaseModel;
use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Gilanggustina\ModuleTreatment\Concerns\HasTreatment;
use Aibnuhibban\ModuleMcu\Resources\McuPackage\{
    ViewMcuPackage,
    ShowMcuPackage
};

class McuPackage extends BaseModel
{
    use SoftDeletes, HasProps, HasTreatment, HasEncoding;

    protected $list  = ['id', 'parent_id', 'name', 'flag', 'props'];
    protected $show  = [];

    protected $casts = [
        'name'              => 'string',
        'company_id'        => 'string',
        'payer_id'          => 'string',
        'agent_id'          => 'string',
        'is_food_handler'   => 'boolean'
    ];

    public function getPropsQuery(): array
    {
        return [
            'company_id'      => 'props->prop_company->id',
            'payer_id'        => 'props->prop_payer->id',
            'agent_id'        => 'props->prop_agent->id',
            'is_food_handler' => 'props->is_food_handler'
        ];
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(function ($query) {
            if (!isset($query->mcu_package_code)) {
                $query->mcu_package_code = static::hasEncoding('MCU_PACKAGE_CODE');
            }
            if (!isset($query->flag)) $query->flag = Flag::MAIN_PACKAGE->value;
        });
    }

    public function toViewApi()
    {
        return new ViewMcuPackage($this);
    }

    public function toShowApi()
    {
        return new ShowMcuPackage($this);
    }

    public function priceComponent()
    {
        return $this->morphOneModel("PriceComponent", "model");
    }
    public function priceComponents()
    {
        return $this->morphManyModel("PriceComponent", "model");
    }
    public function modelHasService()
    {
        return $this->morphOneModel('ModelHasService', 'reference');
    }
    public function modelHasOrganization(?string $morph = null)
    {
        return $this->morphOneModel('ModelHasOrganization', 'reference')
            ->when(isset($morph), function ($query) use ($morph) {
                return $query->where('organization_type', $morph);
            });
    }

    public function organization(?string $morph = null)
    {
        $morph ??= 'Organization';
        return $this->hasOneThroughModel(
            $morph,
            'ModelHasOrganization',
            'reference_id',
            $this->{$morph . 'Model'}()->getKeyName(),
            $this->getKeyName(),
            'organization_id'
        )
            ->where('reference_type', $this->getMorphClass())
            ->when(isset($morph), function ($query) use ($morph) {
                $query->where('organization_type', $morph);
            });
    }

    public function companyHasOrganization()
    {
        return $this->modelHasOrganization($this->CompanyModel()->getMorphClass());
    }

    public function company()
    {
        return $this->organization($this->CompanyModel()->getMorphClass());
    }

    public function agentHasOrganization()
    {
        return $this->modelHasOrganization($this->AgentModel()->getMorphClass());
    }

    public function agent()
    {
        return $this->organization($this->AgentModel()->getMorphClass());
    }

    public function payerHasOrganization()
    {
        return $this->modelHasOrganization($this->PayerModel()->getMorphClass());
    }

    public function payer()
    {
        return $this->organization($this->PayerModel()->getMorphClass());
    }
}
