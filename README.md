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

- Use the Abdo\Searchable\Searchable trait in your model.
- Define the columns you want to use for searching within the searchable array.
- Utilize the search scope to perform searches on the selected columns.

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
    public function searchByCreatedAtDayName(Builder $q, string $searchWord) {
        $q->orWhereRaw("DAYNAME(created_at) like ?", ["%" . $searchWord . "%"]);
    }
}

// usage 

User::search($searchWord)->get();
```

### Search Columns

To define the search columns for a model, you can utilize a property decorated with the `#[SearchColumns]` attribute. 
This property will contain the default columns for searching the model, as well as any relations that need to be eagerly loaded.
In case the `#[SearchColumns]` attribute is not set, the fillable columns will be used as a fallback.

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

If you wish to customize the search query for a specific column, you can create a method with the `#[Search("colname")]` attribute and craft a custom query specifically for that chosen column.

```php
#[SearchColumns]
public $searchable = [
    "columns" => [
        "time", // stored as Y-m-d H:i:s
    ]
];

#[Search("time")]
public function searchTime(Builder $q, string $searchWord) {
     $q->orWhere("time", "like", "%" . $searchWord . "%")
       ->orWhereRaw("DAYNAME(time) like ?", ["%" . $searchWord . "%"]);
}
```

In the example above, the "time" column is stored in the database as a datetime value. If you wish to customize the search functionality for this column by adding a new orWhere statement for searching by day name, you can employ the `#[SearchAdd("colname")]` attribute.

By utilizing this attribute, you can incorporate the desired orWhere statement into the column search query, while still leveraging the existing search query provided by the package.


>[!NOTE]  
you may use as many searchAdd methods as you want. 

```php
#[SearchAdd("time")]
public function searchTimeByDayName(Builder $q, string $searchWord) {
     $q->orWhereRaw("DAYNAME(time) like ?", ["%" . $searchWord . "%"]);
}
```

If you have used either `#[Search("colname")]` or `#[SearchAdd("colname")]` to customize relation search, the builder instance passed to the custom method will be the builder for the relation model.

```php
public $searchable = [
    "columns" => [
        "patient.name",
    ]
];

#[Search("patient.name")]// or #[SearchAdd("patient.name")]
public function searchPatientName(Builder $q, string $searchWord) {
     // $q is builder instance for Patient model 
     $q->orWhere("name", "like", "%" . $searchWord . "%");
}
```

### Overwrite Default Columns

If you want to override the columns defined in the model, you can pass the columns as a second parameter to the search scope.

```php
User::search($searchWord, ["fname", "lname"])->get();
```

### Search Options

You can add an options array for columns

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

The column can have three options

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

By default, the columns used for filtering will be those defined in the property with the `#[SearchColumns]` attribute, combined with the columns specified in the fillable array. If you wish to use different columns as the default for filtering, you can define a property with the `#[FilterColumns]` attribute.


```php
#[FilterColumns]
public $filterable = [
	"name","email", //... 
];
```

### Filter Query String

The query string parameter names should match to the column names. If you are filtering a relation, you can use a colon `:` as a separator between the relation name and the column name, instead of a dot `.` The query string should follow to this pattern.

```php
// ?<colname>=<operator, default:"=">|<value>

User::filter()->get();
```

If you have used a parameter name that differs from the column name, you can pass both the column name and the filter value to the filter scope.

```php
// ?<not-colname>=<operator, default:"=">|<value>
// To retrieve the filter value from the query string, you can utilize the "filterParam" helper

User::filter(["column_name" => filterParam("not_column_name")])->get();

// Note that if the rest of the query string parameter names match the column names
// you can employ a similar approach.
User::filter(["column_name" => filterParam("not_column_name")])->filter()->get(); 
```

### Operators

The operators allowed for filtering include any operator that can be sent to the `where` builder method, plus additional operators.

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

>[!NOTE]  
>When using the "between" operator, two arguments must be provided, separated by a comma. If the `from` argument is not specified, the data will be filtered from negative infinity up to the `to` argument. Similarly, if the `to` argument is not provided, the data will be filtered up to positive infinity.
>For example: ?created_at=bt|,2010-01-01 retrieves all records created before January 1, 2010.
>Another example: ?created_at=bt|2010-01-01, retrieves all records created after January 1, 2010.  

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

If you are utilizing regular Blade templates for the frontend, there is a simple JavaScript script available that simplifies the creation of filter forms. To include this script, add the `@searchableScripts` directive to your main layout.

then you can create filter form like by following steps

### Usage:

- ensure that the form has class `filter`
- The input name should match the corresponding column name
- For relations, use a colon `:` as a separator ex: `relation:columnName`
- Set the desired filtering operator in the `data-filter` attribute, with the default being `=`
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

The "filterParam" helper function is utilized to extract the filter value from the query string. This helper function should be utilized when employing a blade filter form, especially if the input field name differs from the corresponding column name.

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

The "filterValue" helper is employed to retrieve the value utilized for filtering in order to display this value in the input field, as an illustrative example.

```php
//?name=cont|ahmed&role_id=in|2,3,4

filterValue("name") // ouptut ahmed
filterValue("role_id") // output 2,3,4
filterValue("role_id", true) //output [2,3,4]
```

### Custom Operators
In case you need to define a custom operator for filtering and searching, there are two distinct approaches available.

>[!NOTE]
>custom operator must start with `sp_`

**In Config**  
To publish the configuration file, execute the following command

```bash

php artisan vendor:publish --provider="Abdo\Searchable\ServiceProvider"

```
Once the configuration file has been published, you have the option to define your custom operators within the `operators` array.

```php

    "operators" => [

        "sp_is_null" => function (Builder $builder, string $column, string $word) {
            return $builder->whereIsNull($column)->orWhere($column, 0);
        },
    ],

```

**In Service Provider**

Additionally, you can register your custom operators within the boot method of one of the service providers.

```php
    ColumnConfigraution::registerOperator("sp_is_null", function (Builder $builder, string $column, string $word) {
        return $builder->whereIsNull($column)->orWhere($column, 0);
    });

    //after defining your custom operator you can use them like this
    //?status=sp_is_null|
```

