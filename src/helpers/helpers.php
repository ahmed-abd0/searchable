<?php


if (!function_exists("filterParam")) {

    function filterParam(string $queryParam): ?string
    {

        $queryParam = str_replace(".", ":", $queryParam);

        return request()->has($queryParam) && is_array($param = request()->get($queryParam))
            ? ($param["operator"] ?? "=") . "|" . collect($param)->forget("operator")->sortKeys()->implode(",")
            : request()->get($queryParam);
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
