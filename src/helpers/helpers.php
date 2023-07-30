<?php


if (!function_exists("filterParam")) {

    function filterParam(string $queryParam): ?string
    {

        $queryParam = str_replace(".", ":", $queryParam);
        
        return request()->has($queryParam) && is_array($param = request()->get($queryParam))
                ? ($param["operator"] ?? "=") . "|" . collect($param)->forget("operator")->implode(",")
                :   request()->get($queryParam);
    }
}



if (!function_exists("filterValue")) {

    function filterValue(string $queryParam): string|array
    {

        $arr = explode("|", filterParam($queryParam), 2);
        return end($arr);
    }
}
