<?php

namespace Aibnuhibban\ModuleMcu\Schemas;

use Aibnuhibban\ModuleMcu\Contracts\McuServiceItem as ContractsMcuServiceItem;
use Gii\ModuleService\Schemas\ServiceItem as SchemasServiceItem;

class McuServiceItem extends SchemasServiceItem implements ContractsMcuServiceItem {
    protected string $__entity = 'McuServiceItem';
    public static $mcu_service_item_model;    
}
