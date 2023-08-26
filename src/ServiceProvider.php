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

        Builder::macro('orBetweenMacro', function ($column, array $range, $equal = "") {

            [$from, $to] = getFromToFromRange($range);

            return $this->orWhereRaw(
                "($column >{$equal} ? OR ? IS NULL) AND ($column <{$equal} ? OR ? IS NULL)",
                [$from, $from, $to, $to]
            );
        });
    }
}
