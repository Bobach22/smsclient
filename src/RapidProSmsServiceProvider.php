<?php

namespace Bobach22\RapidProSms;

use Illuminate\Support\ServiceProvider;
use Bobach22\RapidProSms\Sms;

class RapidProSmsServiceProvider extends ServiceProvider
{
    protected $configName='sms';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $configPath=__DIR__ . '/config/' . $this->configName . '.php';
        $this->mergeConfigFrom($configPath,$this->configName);

        $this->app->bind('sms',function(){
            return new Sms();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath=__DIR__ . '/config/' . $this->configName . '.php';
        $this->publishes([
            $configPath=>config_path($this->configName . '.php')
        ],'sms');
    }
}
