# Searchable

Laravel  Searchable is package that adds easy customizable  searching and filtering ability to Laravel Eloquent Models 

- [Installation](#installation)

- [Searching](#searching)

  - [Usage Example](#search-usage-example)

  - [Search Columns](#search-columns)

  - [Custom Search](#custom-search)

  - [Overwrite Default Columns](#overwrite-default-columns)

  - [Search Options](#search-options)
   
- [Filtering](#filtering)

  - [Usage Example](#filter-usage-example)

  - [Filtering Columns](#filtering-columns)

  - [Filter Query String](#filter-query-string)
  
  - [Operators](#operators)

  - [Filter Modes](#filter-modes)

  - [Filter Blade Script](#filter-blade-script)

  - [Filter Helpers](#filter-helpers)

  - [Custom Operators](#custom-operators)

## Installation

```bash
composer require ahmedabdo/searchable
```
## Searching

## Search Usage Example

- first use Abdo\Searchable\Searchable trait in your model
- then define the columns you want to use for searching in searchable array like below
- then use the search scope for searching the selected columns

```php
// model
use Abdo\Searchable\Searchable;
use Abdo\Searchable\Attributes\SearchAdd;
use Abdo\Searchable\Attributes\SearchColumns;
class User extends Authenticatable
{
    use Searchable;
	
    #[SearchColumns]
    public $searchable = [
	"columns" => [
	    "name",
	    "email",
	    "role.name",
	    "created_at"
	],
	"eager" => [
	    "role"
	]
    ];

    #[SearchAdd("created_at")]
    public function searchByCreatedAtDayName(Builder $q, $searchWord) {
        $q->orWhereRaw("DAYNAME(created_at) like ?", ["%" . $searchWord . "%"]);
    }
}

// usage 

User::search($searchWord)->get();
```

### Search Columns

you can define your search columns in property that has `#[SearchColumns]` attribute

it will contain the default columns for searching the model and relations that needs

to be eager loaded

if its not set the fillable columns will be used instead

```php
#[SearchColumns]
public $searchable = [
    "columns" => [
        "colame",
	"relation.colname",
        "relation.relation.colname",
    ],
    "eager" => [
        "relation"
    ]
];
```

### Custom Search

if you want to customize searching query for one of the columns you can add method with 

`#[Search(”colname”)]` attribute and write your custom query for the selected column

```php
#[SearchColumns]
public $searchable = [
    "columns" => [
        "time", // stored as Y-m-d H:i:s
    ]
];

#[Search("time")]
public function searchTime(Builder $q, $searchWord) {
     $q->orWhere("time", "like", "%" . $searchWord . "%")
       ->orWhereRaw("DAYNAME(time) like ?", ["%" . $searchWord . "%"]);
}
```

in the example above the time column is stored in the database as datetime you may want to customize searching this column to add new `orWhere` statement for searching by day name 

but for doing that you wrote the same searching query that the package provide so if you want to add new `orWhere` statement for the column search query you may use `#[SearchAdd(”colname”)]` attribute

**_NOTE:_**  you may use as many searchAdd methods as you want. 

```php
#[SearchAdd("time")]
public function searchTimeByDayName(Builder $q, $searchWord) {
     $q->orWhereRaw("DAYNAME(time) like ?", ["%" . $searchWord . "%"]);
}
```

if you used on of `#[Search(”colname”)]` or `#[SearchAdd(”colname”)]` for customizing relation search the builder instance passed to the custom method will be builder for the relation model

```php
public $searchable = [
    "columns" => [
        "patient.name",
    ]
];

#[Search(”patient.name”)]// or #[SearchAdd(”patient.name”)]
public function searchPatientName(Builder $q, $searchWord) {
     // $q is builder instance for Patient model 
     $q->orWhere("name", "like", "%" . $searchWord . "%");
}
```

### Overwrite Default Columns

if you want to overwrite the columns defined in the model you may pass columns as second parameter for the search scope

```php
User::search($searchWord, ["fname", "lname"])->get();
```

### Search Options

you can add options array for columns 

```php
#[SearchColumns]
public $searchable = [
    "columns" => [
        "name" => ["operator" => "="]
    ]
];

User::search($searchWord,[
    "name" => ["operator" => "=", "useCustom" => false, "useAddCondition" => false] 
])
```

the column can have three options

| option |                          description |                          values |      default |
| --- | --- | --- | --- |
| operator | the operator used for searching operation | [Avaliable Operators](#operators) | likeContains |
| useCustom | if it uses search method that has             #[Search(”col”)] | true or false | true |
| useAddCondition | if it uses search methods that has      #[SearchAdd(”col”)] | true or false | true |


## Filtering

### Filter Usage Example

```php
// model
use Abdo\Searchable\Searchable;
class User extends Authenticatable
{
     use Searchable;

     #[SearchColumns]
     public $searchable = [
        "columns" => [
            "name",
            "email",
            "role.name",
            "created_at"
        ],
        "eager" => [
            "role"
        ]
    ];
}

//request query string :
// ?name=ahmed&email=startsWith|ahmed&role:name=admin&created_at=from|2020-01-01 

// usage 
User::filter()->get();
```

### Filtering Columns

the default value for filtering columns will be the columns defined in property that has 

`#[SearchColumns]` attribute merged with column defined in the fillable array if you want to 

use different columns as default for filtering you can define property with `#[FilterColumns]`

attribute

```php
#[FilterColumns]
public $filterable = [
	"name","email", //... 
];
```

### Filter Query String

the query string parameters names should be the same as column names or if you are filtering relation you can use `:` as separator between relation name and column name instead of `.` and the query string should follow this pattern

```php
// ?<colname>=<operator, default:"=">|<value>

User::filter()->get();
```

if you used parameter name different from column name you can pass the column and the filter value to the filter scope

```php
// ?<not-colname>=<operator, default:"=">|<value>
// filterParam is helper to get the filter value from query string
// in this example you can use request("not-colname") to get the value 
User::filter(["column_name" => filterParam("not_column_name")])->get();

// note: if the rest of the query string params names
// are the same as colnames you can do something like this 
User::filter(["column_name" => filterParam("not_column_name")])->filter()->get(); 
```

### Operators

the operators allowed to be use in filtering is any operator you can send to  “where” builder method plus 

|             Operator |                                     Description | Example |
| --- | --- | --- |
| “contains” or “cont”  | filtering out entries where the column value contains the given value | ?name=cont\|ahmed |
| “startsWith” or “sw” | filtering out entries where the column value starts with the given value    | ?name=sw\|ahmed |
| “endsWith” or “ew” | filtering out entries where the column value ends with the given value | ?name=ew\|abdo |
| “In” | filtering out entries where the column value exists in the given values | ?role_id=in\|1,2,3,4,5  |
| “notIn” |  filtering out entries where the column value dosen’t exists in the given values| ?role_id=notIn\|1,5  |
| “is_null” |  filtering out entries where the column value is null  | ?role_id=is_null\|  |
| “is_not_null” |  filtering out entries where the column value is not null | ?role_id=is_not_null\|  |
| “from” | filtering out entries where the `date` column value is after the given value | ?created_at=from\|2010-01-01 |
| “from_eq” | filtering out entries where the `date` column value is after or equal the given value | ?created_at=from_eq\|2010-01-01 |
| “to” | filtering out entries where the `date` column value is before the given value | ?created_at=to\|2010-01-01 |
| “to_eq” | filtering out entries where the `date` column value is before or equal the given value | ?created_at=to_eq\|2010-01-01 |
| “from_time” | same as “from” but for time | ?time=from_time\|12:30 |
| “from_time_eq” | same as “from_eq” but for time | ?time=from_time_eq\|12:30 |
| “to_time” | same as “to” but for time | ?time=to_time\|12:30 |
| “to_time_eq” | same as “to_eq” but for time | ?time=to_time_eq\|12:30 |
| “between” or “bt” | filtering out entries where column value lies in the given range | ?created_at=bt\|2010-01-01,2015-01-01&role_id=bt\|3,5 |
| “betweenEqual” or “bte” | filtering out entries where column value lies in given range with boundry | ?created_at=bte\|2010-01-01,2015-01-01&role_id=bte\|3,5 |
| “where” statement operators | any operator used in “where” method can be used as filter operator | ?age=<\|20&gender=male |

**_NOTE:_**  
between operators must have two arguments separted by comma if there is no `from` argument the it will filter data from minus infinty to the to `to` argument if there is no `to` argument it will filter to infinty  
example : `?created_at=bt|,2010-01-01` get all records created before 2010-01-01  
example : `?created_at=bt|2010-01-01,` get all records created after 2010-01-01  

### Filter Modes

there is two filter modes `and` mode and `or` mode the default mode is `and`  
for changing the mode
```php
    // using or mode
    User::filter(mode: Mode::OR)->get();

    // using and mode
    User::filter(mode: Mode::AND)->get();
    User::filter()->get();
```


### Filter Blade Script

if you are using regular blade for front end there is a simple js script that makes it easier to create filter forms you need to include `“@searcableScripts”`  directive in your main layout 

then you can create filter form like by following steps

### Usage:

- give the form class `filter`
- input name should be like column name
- for relations you can use `:` as separator ex: `relation:columnName`
- set the filtering operator in the `data-filter` attribute the default is `=`
- use `filterValue("queryParam")` to get the filter value

```html
<!-- give the form class  'filter' -->
<form action="" class="filter">
    <label for="">Age</label>
    <!-- place the filter operator in 'data-filter' attribute  -->
    <!-- filterValue helper for getting the current filter value  -->
    <input data-filter=">" type="number" value="{{filterValue('age')}}" name="age" id="">
    
    <label for="">Created At From</label>
    <!-- for bte, bt, betweenEqual, between operators  -->
    <!-- ex: from input with name created_at[0] to input with name created_at[1]  -->
    <input data-filter="bt" type="date" value="{{filterValue('created_at', asArray: true)[0] ?? now()->format('Y-m-d')}}" name="created_at[0]" id="">

    <label for="">Created At To</label>
    <input type="date" value="{{filterValue('created_at', asArray: true)[1] ?? now()->format('Y-m-d')}}" name="created_at[1]" id="">
			
    <!-- ex: for multi value   -->
    <label for="">Role</label>
    <select data-filter="in"  name="role_id[]" id="" multiple>
        <option value="0">admin</option>
        <option value="1">manager</option>
        <option value="2">employee</option>
    </select>

    <button type="submit" >filter</button>
</form>
```

### Filter Helpers

**filterParam**

the filterParam helper function is used to get the filter value from the query string this helper function must be used if you are using blade filter form and the input field name was different from the column name 

```php
//model
#[SearchColumns]
public $searchable = [
    "columns" => [
        "name"
    ],
];

//blade
<form action="" class="filter">
    <input data-filter="contains" type="text" value="{{filterValue('empName')}}" name="empName" id="">
</form>

//usage
User::filter(["name" => filterParam("empName")])->get();
```

**filterValue**

filterValue helper is used to get the value used for filtering to show this value in the input field for example 

```php
//?name=cont|ahmed&role_id=in|2,3,4

filterValue("name") // ouptut ahmed
filterValue("role_id") // output 2,3,4
filterValue("role_id", true) //output [2,3,4]
```

### Custom Operators
you may want to define a custom operator used for filtering and searching there is two ways to define custom operator

>[!NOTE]
>custom operator must start with `sp_`

**In Config**  
to publish config file run this command
```bash

php artisan vendor:publish --provider="Abdo\Searchable\ServiceProvider"

```
after publishing config file you can define your custom operators in the `operators` array

```php

    "operators" => [

        "sp_is_null" => function (Builder $builder, string $column, string $word) {
            return $builder->whereIsNull($column)->orWhere($column, 0);
        },
    ],

```

**In Service Provider**

you can register your custom operators also in one of the service providers boot method

```php
    ColumnConfigraution::registerOperator("sp_is_null", function (Builder $builder, string $column, string $word) {
        return $builder->whereIsNull($column)->orWhere($column, 0);
    });

    //after defining your custom operator you can use them like this
    //?status=sp_is_null|
```

