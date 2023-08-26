<?php

use Abdo\Searchable\ColumnConfigraution;

if (!function_exists("filterParam")) {

    function filterParam(string $queryParam): ?string
    {

        $queryParam = str_replace(".", ":", $queryParam);

        if (request()->has($queryParam) && is_array($param = request($queryParam))) {

            if (in_array(strtoupper($param["operator"] ?? ""), ColumnConfigraution::betweenOperators())) {
                return $param["operator"] . "|" . ($param[0] ?? "") . "," . ($param[1] ?? "");
            }

            return ($param["operator"] ?? "=") . "|" . collect($param)->forget("operator")->implode(",");
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

if (!function_exists("implodeRecursive")) {

    function implodeRecursive($array, $separator = ",")
    {

        $result = '';
        foreach ($array as $value) {
            $result = $result . (is_array($value) ? implodeRecursive($value, $separator) . $separator : $value . $separator);
        }

        return rtrim($result, $separator);
    }
}

if (!function_exists("getFromToFromRange")) {

    function getFromToFromRange(array $range)
    {

        return [
            ($from = $range[0] ?? null) === '' ? null : $from,
            ($to = $range[1] ?? null) === '' ? null : $to
        ];
    }
}
