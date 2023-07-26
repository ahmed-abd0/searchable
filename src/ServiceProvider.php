<?php

namespace Abdo\Searchable;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as laravelServiceProvider;

class ServiceProvider extends laravelServiceProvider {


    public function register()
    {
        
    }

    public function boot()  {
       
        require_once __DIR__ . "/helpers/helpers.php";

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'searchable');

        Blade::directive('searcableScripts', function () {
            return "{!! view('searchable::script')->render() !!}";
        });

    }

}