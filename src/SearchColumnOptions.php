<?php

namespace Abdo\Searchable;


class SearchColumnOptions
{

    public function __construct(private array $options = []) {
    }

    public function operator() : string {
        return $this->options["operator"] ?? "likeContains";
    }

    public function usesCustom() {

        return $this->options["useCustom"] ?? true;
    }

    public function usesAddCondition() {
        return $this->options["useAddCondition"] ?? true;
    }

    public function searchAgruments(string $columnName, string $searchWord) : array {
       
        return match(strtoupper($this->operator())) {
            
            "BETWEEN", "BT" => [$columnName, explode(",", $searchWord, 2)],
            "TO_EQ", "TO_TIME_EQ" => [$columnName, "<=" , $searchWord],
            "FROM_EQ", "FROM_TIME_EQ" => [$columnName, ">=" , $searchWord],
            "TO", "TO_TIME" => [$columnName, "<" , $searchWord],
            "FROM", "FROM_TIME" => [$columnName, ">" , $searchWord],
            "IN", "NOTIN" => [$columnName, explode(",",$searchWord)],
            "LIKEENDSWITH", "LEW" => [$columnName , "like", "%" . $searchWord],
            "LIKESTARTSWITH", "LSW" => [$columnName , "like", $searchWord."%" ],
            "LIKECONTAINS", "LC" => [$columnName , "like", "%".$searchWord."%" ],
            default => [$columnName , $this->operator(), $searchWord]
        };
    }

    public function searchMethod() : string {
        
        return match(strtoupper($this->operator())) {            
            
            "BETWEEN", "BT" => "orWhereBetween",
            "FROM_TIME", "TO_TIME", "FROM_TIME_EQ", "TO_TIME_EQ" => "orWhereTime",
            "FROM", "TO", "FROM_EQ", "TO_EQ" => "orWhereDate",
            "NOTIN" => "orWhereNotIn",
            "IN" => "orWhereIn" ,
            default => "orWhere"
        };
    }



}
