<?php

use Illuminate\Contracts\Database\Eloquent\Builder;

return [

    /** 
     * here you can define your custom operators 
     * 
     * the clouser accepts three parameters the query builder 
     * and the filtered column name, the filter word
     * 
     * note: operator name must start with sp_
     * 
    */

    "operators" => [

        // "sp_operator" => function (Builder $builder, string $column, string $word) {
                
        // },
    ],

];