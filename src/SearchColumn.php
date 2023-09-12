<?php

namespace Abdo\Searchable;

use Abdo\Searchable\AttributeHandler\AttributeHandler;
use Abdo\Searchable\Attributes\Search;
use Abdo\Searchable\Attributes\SearchAdd;
use Abdo\Searchable\Attributes\SearchColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Closure;
use Stringable;

class SearchColumn
{
    public function __construct(
        private Model $model,
        private string $name,
        private ColumnConfigraution $configuration = new ColumnConfigraution()
    ) {
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

    public function nonRelationSearch(Builder $q, string $searchWord)
    {
        $this->callAddConditionCallables($q, $searchWord);
        $q->{$this->configuration->searchMethod()}(...$this->configuration->searchAgruments($this->name, $searchWord));
    }

    public function relationSearch(Builder $q, string $searchWord)
    {

        [$relation, $column] = $this->getRelationAndColumn();

        $q->orWhereHas($relation, function ($q) use ($column, $searchWord) {

            $q->where(function ($q) use ($column, $searchWord) {

                $this->callAddConditionCallables($q, $searchWord);
                $q->{$this->configuration->searchMethod()}(...$this->configuration->searchAgruments($column, $searchWord));
            });
        });
    }

    public function userDefinedMethod(): callable
    {
        if ($this->isRelation()) {
            return $this->relationSearchCustomMethod();
        }

        return $this->customSearchMethod();
    }

    public function getRelationAndColumn(): array
    {
        $reversedParts = $this->strName()->reverse()->explode(".", 2);
        return [strrev($reversedParts[1]), strrev($reversedParts[0])];
    }

    public function isRelation(): bool
    {
        return $this->strName()->contains(".");
    }

    public function hasCustomSearchMethod(): bool
    {
        return (new AttributeHandler($this->model))
            ->findMethods(Search::class, [$this->name])
            ->isNotEmpty();
    }

    protected function relationSearchCustomMethod(): Closure
    {

        return function ($q, $searchWord) {

            [$relation] = $this->getRelationAndColumn();

            $q->orWhereHas($relation, function ($q) use ($searchWord) {

                $q->where(function ($q) use ($searchWord) {
                    $this->customSearchMethod()($q, $searchWord);
                });
            });
        };
    }

    protected function customSearchMethod(): Closure
    {
        return function ($q, $searchWord) {

            (new AttributeHandler($this->model))
                ->findMethod(Search::class, [$this->name])
                ?->invoke($this->model, $q, $searchWord);

            $this->callAddConditionCallables($q, $searchWord);
        };
    }

    protected function callAddConditionCallables(Builder $q, string $searchWord)
    {
        if ($this->configuration->usesAddCondition()) {
            $this->addConditionMethods()->each->invoke($this->model, $q, $searchWord);
        }
    }

    protected function addConditionMethods(): Collection
    {
        return (new AttributeHandler($this->model))->findMethods(SearchAdd::class, [$this->name]);
    }

    protected function searchable(): array
    {
        return (new AttributeHandler($this->model))->findPropertyValue(SearchColumns::class) ?? [];
    }

    private function strName(): Stringable
    {
        return Str::of($this->name);
    }
}
