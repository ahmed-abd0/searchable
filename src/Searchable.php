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

        static::addGlobalScope('filter', function (Builder $builder) {

            $filters = [];
            $builder->getModel()->filterColumns()->each(function ($column) use (&$filters) {

                
                $queryParam = str_replace(".", ":", $column);

                if (request()->has($queryParam)) {
                
                    $filters[$column] = is_array($param = request()->get($queryParam))
                        ? $param["operator"] . "|" . collect($param)->forget("operator")->implode(",")
                        : htmlspecialchars_decode($param);

                }
            });

            $builder->filter($filters);
        });
    }


    public function scopeSearch(Builder $q, $searchWord, ?array $columns = null)
    {
        $q->with($this->eagerLoadRelations())->where(function ($q) use ($columns, $searchWord) {
            collect($columns ?? $this->searchable["columns"] ??  [])->each($this->searchBy($q, $searchWord));
        });
    }

    public function scopeFilter(Builder $q, iterable $filters)
    {

        collect($filters)->each(function ($filterCondition, $column) use ($q) {

            [$operator, $filterWord] = $this->wordAndOperator($filterCondition);
            $q->search($filterWord, [$column => $this->filterOptions($operator)]);
        });
    }

    public function searchColumns(?array $columns = null)
    {
        return collect($columns ?? $this->searchable["columns"] ??  []);
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
