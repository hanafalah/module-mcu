<?php

namespace Hanafalah\ModuleMcu\Schemas;

use Hanafalah\ModuleMcu\Contracts\McuServiceItem as ContractsMcuServiceItem;
use Hanafalah\ModuleService\Schemas\ServiceItem as SchemasServiceItem;

class McuServiceItem extends SchemasServiceItem implements ContractsMcuServiceItem
{
    protected string $__entity = 'McuServiceItem';
    public static $mcu_service_item_model;
}
