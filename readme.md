# A Laravel Nova Field for dependant fields

This field is based on BelongsTo element and the idea from orlyapps/nova-belongsto-depend
which served me as a base/idea to create my first custom field as i had problems implementing
it on Laravel Nova 3

## Installation

You should be able to install this package via composer:

```bash
composer require rumeau/belongsto-depend
```

No additional installation instructions are required 

## Usage

Once installed, you are ready to use the field on your Nova Resources

```php
// in app/Nova/ExampleResource.php
use Rumeau\BelongstoDepend\BelongstoDepend;
use App\Nova\Category;
use App\Nova\Subcategory;

// ...

public function fields()
{
    return [
        // ...
        BelongstoDepend::make(__('Category'), 'category', Category::class),        

        BelongstoDepend::make(__('Subcategory'), 'subcategory', Subcategory::class)
                        ->dependsOn('category', 'parent_id'),
        
        // ...
    ];
}
```

```dependsOn($dependableField, $foreignKey)``` method accepts two arguments:

* **$dependableField**: The field that this field depends from
* **$foreignKey**: A foreign key from which to filter the dependable field options

All BelongsTo core field options are available.

Finally in order to filter the dependable options you can use the core method from 
the resource ```relatable<Relation>()``` in order to filter the query.

## Final notes

This is a work in progress as it may present some errors. So use it at your own risk.

Fork and contribute if you believe it is useful.

Thanks.
