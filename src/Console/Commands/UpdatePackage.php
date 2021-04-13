<?php


namespace Inensus\CalinSmartMeter\Console\Commands;


use Illuminate\Console\Command;
use Inensus\CalinSmartMeter\Services\CalinSmartCredentialService;
use Inensus\CalinSmartMeter\Services\MenuItemService;

class UpdatePackage   extends Command
{
    protected $signature = 'calin-smart-meter:update';
    protected $description = 'Update CalinSmartMeter Package';

    private $menuItemService;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        CalinSmartCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Calin Smart Meter Integration Updating Started\n');
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/calin-smart-meter');
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/calin-smart-meter');


        $this->info('Installing CalinSmartMeter Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Updating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package updated successfully..');

    }
}