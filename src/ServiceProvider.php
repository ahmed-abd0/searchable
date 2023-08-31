<?php

namespace Abdo\Searchable;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as laravelServiceProvider;

class ServiceProvider extends laravelServiceProvider
{



    public function boot()
    {

        require_once __DIR__ . "/Helpers/Helpers.php";

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'searchable');

        Blade::directive('searcableScripts', function () {
            return "{!! view('searchable::script')->render() !!}";
        });


        Builder::macro('orBetweenMacro', function (string $column, array $range, string $equal = "") {

            [$from, $to] = $this->getFromToFromRange($range);

            return $this->orWhereRaw(
                "($column >{$equal} ? OR ? IS NULL) AND ($column <{$equal} ? OR ? IS NULL)",
                [$from, $from, $to, $to]
            );
        });
      
    }

    private function getFromToFromRange(array $range)
    {

        return [
            ($from = $range[0] ?? null) === '' ? null : $from,
            ($to = $range[1] ?? null) === '' ? null : $to
        ];
    }
}
