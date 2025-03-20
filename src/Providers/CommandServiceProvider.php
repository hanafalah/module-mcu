<?php

namespace Hanafalah\ModuleMcu\Providers;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    protected $__commands = [];

    public function register()
    {
        $this->commands(config('module-mcu.commands', $this->__commands));
    }

    public function provides()
    {
        return $this->__commands;
    }
}
