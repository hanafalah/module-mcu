<?php 

use Aibnuhibban\ModuleMcu\{
    Models as ModuleMCU,
    Commands as ModuleMcuCommands
};
use Aibnuhibban\ModuleMcu\Contracts;

return [
    'commands' => [
        ModuleMcuCommands\InstallMakeCommand::class
    ],
    'contracts' => [
        'mcu_category'            => Contracts\McuCategory::class,
        'mcu_package'             => Contracts\McuPackage::class,
        'mcu_service_item'        => Contracts\McuServiceItem::class,
        'mcu_service_price'       => Contracts\McuServicePrice::class,
        'mcu_visit_registration'  => Contracts\McuVisitRegistration::class,
        'module-mcu'              => Contracts\ModuleMcu::class,
    ],
    'database' => [
        'models' => [
            'McuCategory'          => ModuleMCU\McuCategory\McuCategory::class,
            'McuVisitRegistration' => ModuleMCU\McuRegistration\McuVisitRegistration::class,
            'McuPackage'           => ModuleMCU\McuPackage\McuPackage::class,
            'McuServicePrice'      => ModuleMCU\McuServicePrice\McuServicePrice::class,
        ]
    ]
];