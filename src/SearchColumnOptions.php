<?php

namespace Abdo\Searchable;


class SearchColumnOptions
{

    public function __construct(private array $options) {

    }

    public function operator()  {
        return $this->options["operator"] ?? "likeContains";
    }

    public function usesCustom() {

        return $this->options["useCustom"] ?? true;
    }

    public function usesAddCondition(){
        return $this->options["useAddCondition"] ?? true;
    }

    public function searchAgruments(string $columnName, string $searchWord) : array {
       
        return match(strtoupper($this->operator())) {

            "IN", "NOTIN" => [$columnName, explode(",",$searchWord)],
            "LIKEENDSWITH", "LEW" => [$columnName , "like", "%" . $searchWord],
            "LIKESTARTSWITH", "LSW" => [$columnName , "like", $searchWord."%" ],
            "LIKECONTAINS", "LC" => [$columnName , "like", "%".$searchWord."%" ],
            default => [$columnName , $this->operator(), $searchWord]
        };
    }

    public function searchMethod() : string {
        
        return match(strtoupper($this->operator())) {
            "NOTIN" => "orWhereNotIn",
            "IN" => "orWhereIn" ,
            default => "orWhere"
        };
    }


}
