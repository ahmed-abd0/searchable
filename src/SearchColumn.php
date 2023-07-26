<?php

namespace Abdo\Searchable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stringable;

class SearchColumn
{

    private SearchColumnOptions $configuration;

    public function __construct(
        private Model $model,
        private string $name,
        array $configuration = []
    ) {
        $this->configuration = new SearchColumnOptions($configuration);
    }

    public function getColumnQuery(): callable
    {

        if ($this->hasCustomSearchMethod() && $this->configuration->usesCustom()) {
            return $this->userDefinedMethod();
        }

        return function ($q, $searchWord) {

            if ($this->isRelation()) {

                return $this->relationSearch($q, $searchWord);
            }

            $this->nonRelationSearch($q, $searchWord);
        };
    }

    public function nonRelationSearch($q, $searchWord): void
    {
        $this->callAddConditionCallable($q, $searchWord);
        $q->{$this->configuration->searchMethod()}(...$this->configuration->searchAgruments($this->name, $searchWord));
    }


    public function relationSearch($q, $searchWord): void
    {

        [$relation, $column] = $this->getRelationAndColumn();

        $q->orWhereHas($relation, function ($q) use ($column, $searchWord) {

            $q->where(function ($q) use ($column, $searchWord) {

                $this->callAddConditionCallable($q, $searchWord);
                $q->{$this->configuration->searchMethod()}(...$this->configuration->searchAgruments($column, $searchWord));
            });
        });
    }

    public function userDefinedMethod(): callable
    {
        if ($this->isRelation()) {
            return $this->relationSearchCustomMethod();
        }

        return [$this->model, $this->customSearchMethodName()];
    }

    public function conventionMethod(): string
    {
        return "search" . Str::of($this->name)->remove(".");
    }

    public function getRelationAndColumn(): array
    {
        $reversedParts = $this->strName()->reverse()->explode(".", 2);
        return [strrev($reversedParts[1]), strrev($reversedParts[0])];
    }

    public function hasAddConditionToSearchMethod(): bool
    {
        return isset($this->searchable()["add_condition"][$this->name])
            && method_exists($this->model, $this->searchable()["add_condition"][$this->name]);
    }

    public function isRelation(): bool
    {
        return $this->strName()->contains(".");
    }

    public function hasCustomSearchMethod(): bool
    {
        return $this->hasMethodInCustomArray() || $this->hasConventionMethod();
    }

    public function hasMethodInCustomArray(): bool
    {
        return isset($this->searchable()["custom"][$this->name])
            && method_exists($this->model, $this->searchable()["custom"][$this->name]);
    }

    public function hasConventionMethod(): bool
    {
        return method_exists($this->model, "search" . $this->strName()->remove("."));
    }


    protected function relationSearchCustomMethod(): Closure
    {

        return function ($q, $searchWord) {
            [$relation] = $this->getRelationAndColumn();

            $q->orWhereHas($relation, function ($q) use ($searchWord) {

                $q->where(function ($q) use ($searchWord) {
                    [$this->model, $this->customSearchMethodName()]($q, $searchWord);
                });
            });
        };
    }

    protected function customSearchMethodName(): string
    {
        return $this->searchable()["custom"][$this->name] ?? $this->conventionMethod();
    }

    protected function callAddConditionCallable($q, $searchWord): void
    {
        if ($this->hasAddConditionToSearchMethod() && $this->configuration->usesAddCondition()) {
            $this->addConditionToSearchMethod()($q, $searchWord);
        }
    }

    protected function addConditionToSearchMethod(): callable
    {
        return [$this->model, $this->searchable()["add_condition"][$this->name]];
    }


    protected function searchable(): array
    {
        return (fn () => $this->searchable)->call($this->model);
    }

    private function strName(): Stringable
    {
        return Str::of($this->name);
    }
}
