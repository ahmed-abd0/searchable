<?php

namespace Abdo\Searchable;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{

    protected static function booted(): void
    {
        static::addGlobalScope('search', function (Builder $builder) {

            if (request()->has("search") && request()->get("search")) {
                $builder->search(request()->get("search"));
            }
        });

    }

    public function scopeSearch(Builder $q, $searchWord, ?array $columns = null)
    {
        $q->with($this->eagerLoadRelations())->where(function ($q) use ($columns, $searchWord) {
            collect($columns ?? $this->searchable["columns"] ??  [])->each($this->searchBy($q, $searchWord));
        });
    }

    private function searchBy(Builder $q, $searchWord)
    {

        return function ($column) use ($q, $searchWord) {

            $callable = (new SearchColumn($this, $column))->getColumnQuery();

            $callable($q, $searchWord);
        };
    }

    private function eagerLoadRelations() {
        return $this->searchable["eager"] ?? [];
    }

}
