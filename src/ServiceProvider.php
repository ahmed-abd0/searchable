<?php

namespace Abdo\Searchable;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as laravelServiceProvider;

class ServiceProvider extends laravelServiceProvider
{


    public function register()
    {
    }

    public function boot()
    {

        require_once __DIR__ . "/Helpers/Helpers.php";

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'searchable');

        Blade::directive('searcableScripts', function () {
            return "{!! view('searchable::script')->render() !!}";
        });

        Builder::macro('orBetweenEqualMacro', function ($column, array $range) {

            return $this->orWhereRaw(
                "($column >= ? OR ? IS NULL) AND ($column <= ? OR ? IS NULL)",
                [$range[0] ?? null, $range[0] ?? null, $range[1] ?? null, $range[1] ?? null]
            );
        });

        Builder::macro('orBetweenMacro', function ($column, array $range) {

            return $this->orWhereRaw(
                "($column > ? OR ? IS NULL) AND ($column < ? OR ? IS NULL)",
                [$range[0] ?? null, $range[0] ?? null, $range[1] ?? null, $range[1] ?? null]
            );
        });
    }
}
