<?php


if (!function_exists("filterParam")) {

    function filterParam(string $queryParam)
    {
        
        $queryParam = str_replace(".", ":", $queryParam);
       
        return request()->has($queryParam) && is_array($param = request()->get($queryParam))
            ? ($param["operator"] ?? "=") . "|" . collect($param)->forget("operator")->implode(",")
            :   null;
    }
}



if (!function_exists("filterValue")) {

    function filterValue(string $queryParam)
    {
        $arr = explode("|", filterParam($queryParam), 2);
        return end($arr);
    }
}