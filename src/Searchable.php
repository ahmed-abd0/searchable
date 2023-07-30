<?php

namespace Abdo\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\LaravelIgnition\Recorders\DumpRecorder\Dump;

trait Searchable
{

    protected static function booted(): void
    {
        if (static::isUsingAutomaticSearch()) {

            static::addGlobalScope('search', function (Builder $builder) {

                $modelSearch = lcfirst(class_basename(static::class)) . "Search";

                if (request()->has($modelSearch) && request()->get($modelSearch)) {
                    $builder->search(request()->get($modelSearch));
                }
            });
        }
    }

    public function scopeSearch(Builder $q, $searchWord, ?iterable $columns = null)
    {
        $q->with($this->eagerLoadRelations())->where(function ($q) use ($columns, $searchWord) {

            $this->searchColumns($columns)->each($this->searchBy($q, $searchWord));
        });
    }

    public function scopeFilter(Builder $q, ?iterable $filters = null)
    {
        $filters = is_null($filters) ? $this->detectFiltersFromQueryString() : $filters;

        collect($filters)->filter()->each(function ($filterCondition, $column) use ($q) {

            [$operator, $filterWord] = $this->wordAndOperator($filterCondition);

            if ($filterWord !== null && $filterWord !== "") {
                $q->search($filterWord, [$column => $this->filterOptions($operator)]);
            }
        });
    }


    public function detectFiltersFromQueryString()
    {

        $filters = [];

        $this->filterColumns()->each(function ($column) use (&$filters) {
            $filters[$column] = filterParam($column);
        });

        return $filters;
    }

    public function searchColumns(?array $columns = null): Collection
    {
        return collect($columns ?? $this->searchable["columns"] ?? $this->fillable ??  [])->filter();
    }

    public function filterColumns(): Collection
    {
        return $this->searchColumns()->merge($this->fillable ?? []);
    }

    public static function isUsingAutomaticSearch(): bool
    {
        return true;
    }

    private function searchBy(Builder $q, string $searchWord): callable
    {

        return function ($value, $index) use ($q, $searchWord) {

            [$column, $options] = $this->coloumnAndOptions($value, $index);

            $callable = (new SearchColumn($this, $column, new SearchColumnOptions($options)))->getColumnQuery();

            $callable($q, $searchWord);
        };
    }

    private function eagerLoadRelations(): array
    {
        return $this->searchable["eager"] ?? [];
    }

    private function coloumnAndOptions($value, $index): array
    {
        return is_string($index) ? [$index, $value] : [$value, []];
    }

    private function wordAndOperator(string|array $filterCondition): array
    {
        if (is_string($filterCondition)) {
            $filterCondition = explode("|", $filterCondition, 2);
        }

        return count($filterCondition) === 1 ? ["=", $filterCondition[0]] : $filterCondition;
    }

    private function filterOptions($operator): array
    {
        return ["operator" => $operator, "useCustom" => false, "useAddCondition" => false];
    }
}
