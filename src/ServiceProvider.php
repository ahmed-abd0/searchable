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

            $from = ($from = $range[0] ?? null) === '' ? null : $from ;
            $to = ($to = $range[1] ?? null) === '' ? null : $to ;

            return $this->orWhereRaw(
                "($column >= ? OR ? IS NULL) AND ($column <= ? OR ? IS NULL)",
                [$from, $from, $to, $to]
            );
        });

        Builder::macro('orBetweenMacro', function ($column, array $range) {

            $from = ($from = $range[0] ?? null) === '' ? null : $from ;
            $to = ($to = $range[1] ?? null) === '' ? null : $to ;

            return $this->orWhereRaw(
                "($column > ? OR ? IS NULL) AND ($column < ? OR ? IS NULL)",
                [$from, $from, $to, $to]
            );
        });
    }
}
