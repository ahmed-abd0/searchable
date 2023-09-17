<?php

namespace Abdo\Searchable;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ColumnConfigraution
{

    private static array $sepcialOperators = [];

    public function __construct(private array $config = [])
    {
    }

    public function operator(): string
    {
        return $this->config["operator"] ?? "Contains";
    }

    public function usesCustom()
    {
        return $this->config["useCustom"] ?? true;
    }

    public function usesAddCondition()
    {
        return $this->config["useAddCondition"] ?? true;
    }

    public static function betweenOperators()
    {
        return ["BETWEEN", "BT", "BETWEENEQUAL", "BTE"];
    }

    public static function isBetweenOperator(string $operator)
    {
        return in_array(strtoupper($operator), static::betweenOperators());
    }

    public static function registerOperator(string $operator, callable $callable)
    {
        if (!str_starts_with($operator, "sp_")) {
            throw new InvalidArgumentException("custom operator '{$operator}' must start with 'sp_' ");
        }

        static::$sepcialOperators[strtoupper($operator)] = $callable;
    }

    public function searchAgruments(string $columnName, string $searchWord): array
    {

        if ($this->operatorIsSepcial()) {
            return [$this->sepcialOperatorArg($columnName, $searchWord)];
        }

        return match ($this->upperCaseOperator()) {

            "IS_NULL", "IS_NOT_NULL" => [$columnName],
            "BETWEENEQUAL", "BTE" => [$columnName, explode(",", $searchWord, 2), "="],
            "BETWEEN", "BT" => [$columnName, explode(",", $searchWord, 2)],
            "TO_EQ", "TO_TIME_EQ" => [$columnName, "<=", $searchWord],
            "FROM_EQ", "FROM_TIME_EQ" => [$columnName, ">=", $searchWord],
            "TO", "TO_TIME" => [$columnName, "<", $searchWord],
            "FROM", "FROM_TIME" => [$columnName, ">", $searchWord],
            "IN", "NOTIN" => [$columnName, explode(",", $searchWord)],
            "ENDSWITH", "EW" => [$columnName, "like", "%" . $searchWord],
            "STARTSWITH", "SW" => [$columnName, "like", $searchWord . "%"],
            "CONTAINS", "CONT" => [$columnName, "like", "%" . $searchWord . "%"],
            default => [$columnName, $this->operator(), $searchWord]
        };
    }

    public function searchMethod(): string
    {

        if ($this->operatorIsSepcial()) return "tap";

        return match ($this->upperCaseOperator()) {

            "IS_NOT_NULL" => "orWhereNotNull",
            "IS_NULL" => "orWhereNull",
            "BETWEEN", "BT", "BETWEENEQUAL", "BTE" => "orBetweenMacro",
            "FROM_TIME", "TO_TIME", "FROM_TIME_EQ", "TO_TIME_EQ" => "orWhereTime",
            "FROM", "TO", "FROM_EQ", "TO_EQ" => "orWhereDate",
            "NOTIN" => "orWhereNotIn",
            "IN" => "orWhereIn",
            default => "orWhere"
        };
    }

    public function operatorIsSepcial(): bool
    {
        return isset(static::$sepcialOperators[$this->upperCaseOperator()]);
    }

    public function sepcialOperatorArg(string $columnName, string $searchWord): Closure
    {
        return function (Builder $q) use($columnName, $searchWord) {
            return $q->orWhere(
                fn (Builder $q) => static::$sepcialOperators[$this->upperCaseOperator()]($q, $columnName, $searchWord)
            );
        };
    }

    public function upperCaseOperator() {
        return strtoupper($this->operator());
    }
}
