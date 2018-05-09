# crud
Easy CRUDs

# Install
```
composer require kairossystems/crud
```

# How to use

Create a new controller
```
php artisan make:controller TestController
```

Add properties to this new controller
```php
<?php

namespace App\Http\Controllers;

use KairosSystems\CRUD\BaseController;
use Illuminate\Http\Request;
use App\ModelClass;

class TestController extends BaseController
{
    protected $class = ModelClass::class;
    protected $rulesCreate = [
        'name' => 'required|unique:users'
    ];

}
```
Now you have the index store, update and delete methods automagically

## NOTES
You need the fillable properties in your model
