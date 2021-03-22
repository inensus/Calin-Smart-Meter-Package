<?php

namespace Inensus\CalinSmartMeter\Providers;

use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\CalinSmartMeter\CalinSmartMeterApi;
use Inensus\CalinSmartMeter\Console\Commands\InstallPackage;
use Inensus\CalinSmartMeter\Helpers\ApiHelpers;
use Inensus\CalinSmartMeter\Http\Requests\CalinSmartMeterApiRequests;
use Inensus\CalinSmartMeter\Models\CalinSmartCredential;
use Inensus\CalinSmartMeter\Models\CalinSmartTransaction;

class CalinSmartMeterServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/calin-smart-meter.php', 'calin-smart-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('CalinSmartMeterApi', function () {
            $client = new Client();
            $meterParameter = new MeterParameter();
            $transaction = new Transaction();
            $calinSmartTransaction = new CalinSmartTransaction();
            $mainSettings = new MainSettings();
            $calinSmartCredential = new CalinSmartCredential();
            $manufacturer = new Manufacturer();
            $apiHelpers = new ApiHelpers($manufacturer);
            $apiRequests = new CalinSmartMeterApiRequests($client, $apiHelpers, $calinSmartCredential);
            return new CalinSmartMeterApi(
                $client,
                $meterParameter,
                $calinSmartTransaction,
                $transaction,
                $mainSettings,
                $calinSmartCredential,
                $apiRequests,
                $apiHelpers
            );
        });
    }
    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/calin-smart-meter.php' => config_path('calin-smart-meter.php'),
        ]);
    }
    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/calin-smart-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_calin_smart_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path . '*_create_calin_smart_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_calin_smart_tables.php")
            ->first();
    }
}
