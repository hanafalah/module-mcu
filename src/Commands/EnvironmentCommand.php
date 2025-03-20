<?php

namespace Aibnuhibban\ModuleMcu\Commands;

use Zahzah\LaravelSupport\{
    Commands\BaseCommand
};
use Zahzah\LaravelSupport\Concerns\ServiceProvider\HasMigrationConfiguration;

class EnvironmentCommand extends BaseCommand{
    use HasMigrationConfiguration;

    protected function init(): self{
        //INITIALIZE SECTION
        $this->setLocalConfig('module-patient');
        return $this;
    }

    protected function dir(): string{
        return __DIR__.'/../';
    }    
}
