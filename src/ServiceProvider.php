<?php

namespace Abdo\Searchable;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as laravelServiceProvider;

class ServiceProvider extends laravelServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/searchable.php',
            'searchable'
        );
    }

    public function boot()
    {

        require_once __DIR__ . "/helpers/helpers.php";

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'searchable');

        Blade::directive('searcableScripts', function () {
            return "{!! view('searchable::script')->render() !!}";
        });

        $this->publishes([
            __DIR__ . '/../config/searchable.php' => config_path('searchable.php'),
        ], "config");

        $this->registerBuilderMacros();
        $this->registerSepcialOperatorsFromConfig();
    }

    private function registerBuilderMacros()
    {

        Builder::macro('orBetweenMacro', function (string $column, array $range, string $equal = "") {

            [$from, $to] = getFromToFromRange($range);

            return $this->orWhere(function (Builder $q) use ($column, $equal, $from, $to) {

                $q->when($from, fn ($q) =>  $q->where($column, ">{$equal}", $from))
                    ->when($to, fn ($q) =>  $q->where($column, "<{$equal}", $to));
            });
        });
    }

    private function registerSepcialOperatorsFromConfig()
    {

        foreach (config("searchable.operators", []) as $operator => $callable) {
            ColumnConfigraution::registerOperator($operator, $callable);
        }
    }
}
