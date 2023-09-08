<?php

namespace Abdo\Searchable;

use Abdo\Searchable\AttributeHandler\AttributeHandler;

class ColumnConfigraution
{

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

    public function searchAgruments(string $columnName, string $searchWord): array
    {

        return match (strtoupper($this->operator())) {

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

        return match (strtoupper($this->operator())) {

            "BETWEEN", "BT", "BETWEENEQUAL", "BTE" => "orBetweenMacro",
            "FROM_TIME", "TO_TIME", "FROM_TIME_EQ", "TO_TIME_EQ" => "orWhereTime",
            "FROM", "TO", "FROM_EQ", "TO_EQ" => "orWhereDate",
            "NOTIN" => "orWhereNotIn",
            "IN" => "orWhereIn",
            default => "orWhere"
        };
    }

   
}
