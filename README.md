# Searchable

Laravel  Searchable is package that adds easy customizable  searching and filtering ability to Laravel Eloquent Models 

## Installation

```php
composer require ahmedabdo/searchable
```

## Usage Example

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

to be eager loaded with searching 

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

if you want to customize searching query for on of the columns you can add method with 

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

in the example above the time column is stored in the database as date time you may want to customize searching this column to add new `orWhere` statement for searching by day name 

but for doing that you wrote the same searching that the package provide so if you want to add new `orWhere` statement for the column search query you may use `#[SearchAdd(”colname”)]` attribute you may as many methods using this attribute as you want

```php
#[SearchAdd("time")]
public function searchTimeByDayName(Builder $q, $searchWord) {
     $q->orWhereRaw("DAYNAME(time) like ?", ["%" . $searchWord . "%"]);
}
```

if you used on of `#[Search(”colname”)]` or `#[SearchAdd(”colname”)]` for customizing relation search the builder instance passed to the custom method will be the builder for the relation model

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
User::seach($searchWord, ["fname", "lname"])->get();
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
| operator | the operator used for searching operation | will be explained later in filtering part | likeContains |
| useCustom | if it uses search method that has             #[Search(”col”)] | true or false | true |
| useAddCondition | if it uses search methods that has      #[SearchAdd(”col”)] | true or false | true |
