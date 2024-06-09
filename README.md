# selector-service
**SelectorService** - Service for selecting date from API


## Installation

Use [Composer](https://getcomposer.org "Composer")

>composer require garkavenkov/selector-service

## Usage

### Initialization

In model contoroller create (or add if method allready exists) __construct.

```php

use Garkavenkov\Laravel\SelectorService;

class [ControllerName] extends Controller
{
    protected $selector;

    public function __construct(SelectorService $selector)
    {
        $this->selector = $selector;
    }

    public function index()
    {
        $data = $this->selector->get([ModelClass]::class);

        return $data;
    }    

    ... 
}
```

In [ModelClass] add protected static array containing model's allowed fields for data selection and function 


```php

class [ModelClass] extends Model
{
    use HasFactory;

    protected static $filterable = [
        'id',
        'name',
        ...

    ];

    ...

    public static function getFilterableFields()
    {
        return self::$filterable;
    }
}

```

All condition passed through the query string

## where

For example:

>http://localhost:8000/api/people?where=name=John

Return records from table in which field **name** has value **John**

It is also possible to select data for several coditions

>http://localhost:8000/api/people?where=name=John;age=20

Return records from table in which **John** is **20** years old

---

## per_page

For pagination data, pass **per_page** parameter with records count per page

>http://localhost:8000/api/people?per_page=10

Return first 10 records

---

## page

If parameter **per_page** was sent, it is passible to pass **page** parameter.

>http://localhost:8000/api/people?per_page=10&page=2

Return records start from **11** till **20**

--- 


## with

If model has relationship, it is possible to return record(s) with relationship data.

For expamle:

Model **Person** has relationship **address**

>http://localhost:8000/api/people?where=name=John&with=address

Return **Person** collection(s) with relationship

It is possible to use several relationships

>http://localhost:8000/api/people?where=name=John&with=address,hobbies
---

## sort

For sorting result add parameter **sort** 

>http://localhost:8000/api/people?where=name=John&with=address&sort=age

Result will be sorting on field **age** ascending
For descending sorting use symbol **-** before field name

>http://localhost:8000/api/people?where=name=John&with=address&sort=-age

It is also possible sorting on several fields

>http://localhost:8000/api/people?where=name=John&sort=-age,hight

---
## scope

It is also possible select data with scope

>http://localhost:8000/api/people?where=name=John&scope=programmer

Return all persons for scope **programmer** and then with name **John**

Of course it's possible to use several scopes 

>http://localhost:8000/api/people?where=name=John&scope=programmer,php

---

## random

>http://localhost:8000/api/people?random=N

Return **N** random record

---


## fields

It is possible to select particular fields form the record

>http://localhost:8000/api/people?where=name=John&fields=id,name,age

Return only fields **id**, **name**, **age**