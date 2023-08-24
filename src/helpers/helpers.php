<?php


if (!function_exists("filterParam")) {

    function filterParam(string $queryParam): ?string
    {

        $queryParam = str_replace(".", ":", $queryParam);

        if (request()->has($queryParam) && is_array($param = request($queryParam))) {

            if (in_array($param["operator"] ?? "", ["bt", "bte", "between", "betweenEqual"])) {
                
                return $param["operator"] . "|" . ($param[0] ?? "") .",".($param[1] ?? "");
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
