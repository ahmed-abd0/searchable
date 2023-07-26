<?php

namespace Abdo\Searchable;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{

    protected static $automaticSearch = true;
    protected static $automaticFilter = true;
    protected static $useSearchableGloabalScopes = true;

    protected static function booted(): void
    {
        if(static::$useSearchableGloabalScopes && static::$automaticSearch) {
            
            static::addGlobalScope('search', function (Builder $builder) {
    
                if (request()->has("search") && request()->get("search")) {
                    $builder->search(request()->get("search"));
                }
            });
        }

        if(static::$useSearchableGloabalScopes && static::$automaticFilter) {
          
            static::addGlobalScope('filter', function (Builder $builder) {

                $filters = [];
               
                $builder->getModel()->filterColumns()->each(function ($column) use (&$filters) {
    
                    $filters[$column] = filterParam($column) ;
                });
    
                $builder->filter($filters);
            });
        }
       
    }


    public function scopeSearch(Builder $q, $searchWord, ?iterable $columns = null)
    {
        $q->with($this->eagerLoadRelations())->where(function ($q) use ($columns, $searchWord) {

            $this->searchColumns($columns)->each($this->searchBy($q, $searchWord));
        });
    }

    public function scopeFilter(Builder $q, iterable $filters)
    {

        collect($filters)->filter()->each(function ($filterCondition, $column) use ($q) {

            [$operator, $filterWord] = $this->wordAndOperator($filterCondition);
            $q->search($filterWord, [$column => $this->filterOptions($operator)]);
        });
    }

    public function searchColumns(?array $columns = null)
    {
        return collect($columns ?? $this->searchable["columns"] ??  [])->filter();
    }

    public function filterColumns()
    {
        return $this->searchColumns()->merge($this->fillable ?? []);
    }

    private function searchBy(Builder $q, $searchWord)
    {

        return function ($value, $index) use ($q, $searchWord) {

            [$column, $options] = $this->coloumnAndOptions($value, $index);

            $callable = (new SearchColumn($this, $column, $options))->getColumnQuery();

            $callable($q, $searchWord);
        };
    }


    private function eagerLoadRelations()
    {
        return $this->searchable["eager"] ?? [];
    }

    private function coloumnAndOptions($value, $index)
    {
        return is_string($index) ? [$index, $value] : [$value, []];
    }

    private function wordAndOperator($filterCondition)
    {
        $result = explode("|", $filterCondition, 2);
        return count($result) === 1 ? ["=", $result[0]] : $result;
    }

    private function filterOptions($operator)
    {
        return ["operator" => $operator, "useCustom" => false, "useAddCondition" => false];
    }
}
