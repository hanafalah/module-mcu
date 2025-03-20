<?php

declare(strict_types=1);

namespace Hanafalah\ModuleMcu;

use Hanafalah\LaravelSupport\Providers\BaseServiceProvider;

class ModuleMcuServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return $this
     */
    public function register()
    {
        $this->registerMainClass(ModuleMcu::class)
            ->registerCommandService(Providers\CommandServiceProvider::class)
            ->registers([
                '*',
                'Services' => function () {
                    $this->binds([
                        Contracts\ModuleMcu::class => ModuleMcu::class,
                        Contracts\McuCategory::class => Schemas\McuCategory::class,
                        Contracts\McuVisitRegistration::class => Schemas\McuVisitRegistration::class,
                        Contracts\McuPackage::class => Schemas\McuPackage::class,
                        Contracts\McuServiceItem::class => Schemas\McuServiceItem::class,
                        Contracts\McuServicePrice::class => Schemas\McuServicePrice::class,
                    ]);
                }
            ]);
    }

    /**
     * Get the base path of the package.
     *
     * @return string
     */
    protected function dir(): string
    {
        return __DIR__ . '/';
    }

    protected function migrationPath(string $path = ''): string
    {
        return database_path($path);
    }
}
