<?php

namespace App\Providers;

use App\Serializer\ISerializer;
use App\Serializer\StringSerializer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ISerializer::class, StringSerializer::class);
    }
}
