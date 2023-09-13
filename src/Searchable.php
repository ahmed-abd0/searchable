<?php

namespace Abdo\Searchable;

use Abdo\Searchable\AttributeHandler\AttributeHandler;
use Abdo\Searchable\Attributes\FilterColumns;
use Abdo\Searchable\Attributes\SearchColumns;
use Abdo\Searchable\Enums\Mode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

            $this->searchColumns($columns)->each($this->searchCallable($q, $searchWord));
        });
    }

    public function scopeFilter(Builder $q, ?iterable $filters = null, Mode $mode = Mode::AND)
    {
        $filters = is_null($filters) ? $this->detectFiltersFromQueryString() : $filters;

        if ($mode === Mode::OR) {
            return tap($q, fn() => collect($filters)->filter()->each($this->orFilterCallable($q)));
        } 

        collect($filters)->filter()->each($this->andFilterCallable($q));
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
        return collect($columns ?? $this->searchable()["columns"] ?? $this->fillable ??  [])->filter();
    }

    public function filterColumns(): Collection
    {
        return $this->filterable()
            ? collect($this->filterable())
            : $this->searchColumns()->merge($this->fillable ?? []);
    }

    public static function isUsingAutomaticSearch(): bool
    {
        return true;
    }

    private function searchCallable(Builder $q, string $searchWord): callable
    {

        return function ($value, $index) use ($q, $searchWord) {

            [$column, $config] = $this->coloumnAndConfig($value, $index);

            $query = (new SearchColumn($this, $column, $config))->getColumnQuery();

            $query($q, $searchWord);
        };
    }

    private function andFilterCallable(Builder $q)
    {
        return function ($filterCondition, $column) use ($q) {

            [$operator, $word] = $this->wordAndOperator($filterCondition);

            $q->search($word, [$column => $this->filterConfig($operator)]);
        };
    }

    private function orFilterCallable(Builder $q)
    {
        return function ($filterCondition, $column) use ($q) {

            [$operator, $word] = $this->wordAndOperator($filterCondition);

            $query = (new SearchColumn($this, $column, $this->filterConfig($operator)))->getColumnQuery();

            $query($q, $word);
        };
    }

    private function eagerLoadRelations(): array
    {
        return $this->searchable()["eager"] ?? [];
    }

    private function coloumnAndConfig($value, $index): array
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

    private function filterConfig($operator): array
    {
        return ["operator" => $operator, "useCustom" => false, "useAddCondition" => false];
    }

    private function searchable(): ?array
    {
        return (new AttributeHandler($this))
            ->findPropertyValue(SearchColumns::class);
    }

    private function filterable(): ?array
    {
        return (new AttributeHandler($this))
            ->findPropertyValue(FilterColumns::class);
    }
}
