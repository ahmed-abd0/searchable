<?php

use Abdo\Searchable\ColumnConfigraution;

if (!function_exists("filterParam")) {

    function filterParam(string $queryParam): ?string
    {

        $queryParam = str_replace(".", ":", $queryParam);

        if (request()->has($queryParam) && is_array($param = request($queryParam))) {

            return ColumnConfigraution::isBetweenOperator($param["operator"] ?? '')
                ? $param["operator"] . "|" . ($param[0] ?? "") . "," . ($param[1] ?? "")
                : ($param["operator"] ?? "=") . "|" . collect($param)->forget("operator")->implode(",");
        }

        return request($queryParam);
    }
}

if (!function_exists("filterValue")) {

    function filterValue(string $queryParam, bool $asArray = false): string|array
    {

        $arr = explode("|", filterParam($queryParam), 2);

        return $asArray && str_contains(end($arr), ",") ? explode(",", end($arr)) : end($arr);
    }
}

