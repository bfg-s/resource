# Extension resource

## Install
```bash
composer require bfg/resource
```

## Description
A small addition to the resources to the `Laravel`, 
which is a dumb modifiable due to the overridency of 
the main team of the `Laravel` to the new Stub in which 
another resource inheritance is included which, in turn, 
everything is also inherited from 
`Illuminate\Http\Resources\Json\JsonResource` so that 
the reasons of the `Laravel` will understand it as a 
full-fledged resource.

## About the concept.
The concept of expansion is to reduce resources and make 
them more flexible and versatile. The flexibility lies 
in the fact that each field is to have an independent 
additional possibility of obtaining data on paths and 
the use of the `Laravel Eloquent Casts` and `Laravel Eloquent Mutators`. 
Also knows how to determine the relationship of models and connect the fields when 
they are loaded. Rule for pagination.

## Possibilities
The possibilities of this package in aggregate with the `bfg/route` pack allow me to 
organize a not big but very powerful pattern for api that can compete with 
convenience with the `GraphQL`.

## Where can I use it?
This package is intended only in order to make an add-in over the framework Laravel. 
With the full use of the package, it is rapidly back and efficiently implement a 
resistant API resource for models and not only.

## Create new resource
In order to create a resource, it is enough to use the standard Command:
```bash
php artisan make:resource user
```
I added some properties there:
```bash
-m, --model[=MODEL]   Create with the model
-r, --route           Create with route
-c, --collection      Create a resource collection
```
After execution, the `app/Http/Resources/UserResource.php` file will 
be created with the following contents:
```php
<?php

namespace App\Http\Resources;

use App\Models\User;
use Bfg\Resource\BfgResource;
use Bfg\Resource\Traits\ModelScopesTrait;
use Bfg\Resource\Traits\EloquentScopesTrait;

/**
 * @mixin User
 */
class UserResource extends BfgResource
{
    use EloquentScopesTrait, ModelScopesTrait;

    /**
     * Map of resource fields
     * @var array
     */
    protected array $map = [

    ];
}
```
Further, all we need is to fill out a resource map.
```php
...
    protected array $map = [
        'id',
        'name',
        'email',
        'phone',
        'photo',
        // Variants
        'avatar' => AvatarResource::class, // Use resource
        'avatar' => [AvatarResource::class, 'photo'], // Path to field
        'avatar' => [AvatarResource::class, 'photo.src'], // You can use a dot path, for relations an example
        'avatar' => ['photo.src'], // Or just set a new path for field in array
        'avatar' => 'photo.src', // Or in string
    ];
...
```

## Mutators
For example, in the case of the user, for its photo we need to 
process the data for this field and for it we can use the mutator for it. 
All mutators are determined by the same rules as in the `Laravel` models only 
instead of `Attribute` write `Field`.
```php
...
    public function getPhotoField ($value) {
        return $value ? asset($value) : asset('images/default_photo.jpg');
    }
...
```

## Casting
All resource casting rules are completely copied from the 
[casting of `Laravel Attributes`](https://laravel.com/docs/8.x/eloquent-mutators#attribute-casting) 
and their functionality is absolutely identical (except for the `set` of custom casts):
```php
...
    protected array $casts = [
        'id' => 'int'
    ];
...
```

## Extending (Re-use)
Combines the results of resource fields. 
Performed before the main resource for redefining the parent.
> Important! Common resources should work with one model.
```php
...
    protected array $extends = [
        UserResource::class => ['id', 'name'], // Insert only id and name fields
        UserDetailsResource::class => 'phone', // Insert only phone field
        UserCommentResource::class, // Inserted all fields
    ];
...
```

## Routing
To use resources as API Controllers, I recommend that you use 
[Laravel Fortify](https://laravel.com/docs/8.x/fortify) or 
[JetStream](https://jetstream.laravel.com) as an API provider.

### Definition of routs
Before you start, you need install the package [bfg/route](https://packagist.org/packages/bfg/route):
```bash
composer require bfg/route
```
In your `RouteServiceProvider` Add the pointer to search for routs:
```php
...
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            ...
            Route::find(
                __DIR__.'/../Resources',
                Route::prefix('api')
                    ->middleware(['api', 'auth:sanctum'])
                    ->as('api.')
            );
            
            //Route::prefix('api')
            //    ->middleware('api')
            //    ->namespace($this->namespace)
            //    ->group(base_path('routes/api.php'));            
            ...
        });
    }
...
```
Then you need to add an attribute to the resource class:
```php
...
use Bfg\Resource\Attributes\GetResource;

/**
 * @mixin User
 */
#[GetResource] 
class UserResource extends BfgResource
{
    ...
}
```
After that, you will appear in the route link `api/user/{scope}` 
that refers to this resource.

### Scopes in routes
All resource skys should be static and public.
By linking the parameters of `scopes` must be understood as they work.
The idea of a sequential call of `scopes` is as follows:
The sequence of them is fixed sequentially through the slash in the query reference,
for example:
```url
[GET] http://example.com/api/user/get/only/...
```
In the result you will receive a list of users without any fields as `Only` 
accepts the parameters, namely the fields that need to be displayed. In order 
to transfer the fields in `scope`, we use the same syntax as the sequential call 
only after each `scope` goes its parameters, the resource controller processes 
the string sequentially and if `scope` is found in this sequence, it needs to be 
performed. All other parameters after it are considered to be the parameters of 
the `scope` if the name of the other (next) `scope` does not occur in these names 
and so to the end.

This is how the request for obtaining data with pagination and field filtering is:
> Important! Your resource must be connected to the trait
> `Bfg\Resource\Traits\EloquentScopesTrait` and `Bfg\Resource\Traits\ModelScopesTrait`

```url
[GET] http://example.com/api/user/paginate/15/only/id/name
[GET] http://example.com/api/user/[:paginate]/[perPage]/[:only]/[field]/[field]
```

Each of each `scope` is single for each pre request, on the`scope`

After pagination, the collection also returns, and in this case, 
each of its recording is processed `only` scope.

Fields that have not entered the resource after `scope` `only` Fill the `null`

Put a question mark `?` In front of the field name in the resource map:
```php
...
    protected array $map = [
        '?id', // <--
    ];
...
```
Fill in the resource an array with fields that must be temporary:
```php
...
    protected array $temporal = [
        'id', // <--
    ];
...
```
Put the trigger to `true`, which will make temporary all resource fields:
```php
...
    protected bool $temporal_all = true;
...
```

### Scopes in resource
In the class of the `Scopes` resource look like public static functions with the 
name in `CamelCase` and postfix `Scope`

Consider what the standard `Resource Eloquent Get Scope`:
```php
...
    public static function getScope($model): mixed
    {
        return $model->get();
    }
...
```
The first parameter in `Scope` goes the current model that came either from the 
previous `scope` either the standard resource model that will try to determine 
automatically depending on the name of the resource, or you must specify the 
model in the `BfgResource::$model` parameter or override the 
`public static function getResource(): mixed`.

Now consider the `only` `scope`, where we accept all the parameters sequentially:
```php
...
    public static function onlyScope($model, ...$fields): mixed
    {
        return $model?->only($fields);
    }
...
```
```php
...
    public static function paginateScope(
        $model,
        int $perPage = null,
        string $pageName = 'page',
        int $page = null,
        ...$columns
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        /** @var Model $model */
        return $model->paginate($perPage, $columns ?: ['*'], $pageName, $page);
    }
...
```
Thus, we first get the entire list, and then consistently, and the validation of the 
required parameters will pass by the `PHP` means.

The same Rout resource takes various query methods, for such methods there are individual `scope`
We formally alike add the method name in the name of the `updatePostScope` function - for `post`

### Default scopes
For more convenience, in the set there are already ready-made `scope` which I can make it easier for you
Development.

#### EloquentAllScopeTrait
Trait to add `scope` Receiving all records.
```url
[GET] http://example.com/api/user/all
[GET] http://example.com/api/user/[all]
```

#### EloquentFindScopeTrait
Trait to add `scope` Search by `id`.
```url
[GET] http://example.com/api/user/find/1
[GET] http://example.com/api/user/[find]/[id]
```

#### EloquentFirstScopeTrait
Trait to add `scope` Gets of the first record.
```url
[GET] http://example.com/api/user/first
[GET] http://example.com/api/user/[first]
```

#### EloquentForPageScopeTrait
Trait to add `scope` To get pagination data for the page.
```url
[GET] http://example.com/api/user/for_page/2
[GET] http://example.com/api/user/[for_page]/[page]/[perPage=15]
```

#### EloquentLatestScopeTrait
Trait to add `scope` To get the latest row.
```url
[GET] http://example.com/api/user/latest
[GET] http://example.com/api/user/[latest]/[column=id]
```

#### EloquentLimitScopeTrait
Trait to add `scope` To obtain the specified amount of data.
```url
[GET] http://example.com/api/user/limit/3
[GET] http://example.com/api/user/[limit]/[count]
```

#### EloquentOrderByScopeTrait
Trait to add `scope` To obtain assorted data.
```url
[GET] http://example.com/api/user/order_by/id
[GET] http://example.com/api/user/[order_by]/[column]/[direction=asc]
```
```url
[GET] http://example.com/api/user/order_by_desc/id
[GET] http://example.com/api/user/[order_by_desc]/[column]
```

#### EloquentPaginateScopeTrait
Trait to add `scope` Receive records with pagination.
```url
[GET] http://example.com/api/user/paginate
[GET] http://example.com/api/user/[paginate]/[perPage=null]/[pageName=page]/[page=null]
```

#### EloquentRandomScopeTrait
Trait to add `scope` To obtain random data.
```url
[GET] http://example.com/api/user/random
[GET] http://example.com/api/user/[random]/[seed=]
```

#### EloquentSkipScopeTrait
Trait to add `scope` To skip the entries on the ID.
```url
[GET] http://example.com/api/user/skip
[GET] http://example.com/api/user/[skip]/[ids]...
```

#### EloquentWhereScopeTrait
Trait to add `scope` Conditions for request.
```url
[GET] http://example.com/api/user/where/phone/3800000000
[GET] http://example.com/api/user/[where]/[column]/[condition]/[value=null]
```

#### EloquentWithScopeTrait
Trait to add `scope` Loading the resource relationship.
```url
[GET] http://example.com/api/user/with/commentaries-images/profile
[GET] http://example.com/api/user/[with]/[relation->relation]/[relation->relation]...
```
Adding to the download works in depth to each relationship through `-`.

#### ModelOnlyScopeTrait
Trait to add `scope` Field restrictions, it works after sample.
```url
[GET] http://example.com/api/user/first/only/id
[GET] http://example.com/api/user/[first]/[only]/[field]/[field]...
```

#### EloquentScopesTrait
General trait for connecting all `Eloquent` `scope`.

#### ModelScopesTrait
General trait for connecting all `Model` `scope`.

### Final for the query method.
For the `GET` method, the standard Finish `scope` `All` works. 
For the method of `OPTIONS` `scope` `first`.

## Policy
To protect with `Laravel Policy`, I added you attributes that are responsible for it.

### CanResource
Attribute for checking `resource`.
```php
#[GetResource, CanResource]
class DirectorResource extends BfgResource
{
    ...
}
```
Will check the `id-field-user`.

### CanScope
Attribute for checking `scope`.
```php
use Bfg\Resource\Attributes\CanScope;
...
    #[CanScope]
    public static function myScope($model, array $data, int $id): mixed
    {
        return $model;
    }
...
```
Since this conditional resource `UserResource` More will be checked by the politics `my-user`
Or you can specify your own `#[CanScope('my-policy')]`.

### CanFields
Attribute for checking field or fields. It only applies to the `map` parameter.
```php
...
    #[CanFields([
        'id', 'name'
    ])] protected array $map = [
        'id',
        'name',
    ];
    // Or
    #[CanFields('id', 'name')] 
    protected array $map = [
        'id',
        'name',
    ];
...
```
Will check the `id-field-user` and `name-field-user`.
```php
...
    #[CanFields([
        'id', 'name' => 'my-policy'
    ])] protected array $map = [
        'id',
        'name',
    ];
...
```
Will check the `id-field-user` and `my-policy`.

If the policy field does not fit, it is simply not present in the overall list.

### CanUser
Checks the user fields with resource fields.
```php
#[GetResource, CanUser]
class DirectorResource extends BfgResource
{
    ...
}
```
Will check `resource->user_id == auth->user->id`.

Or you can manually specify which fields to check.
```php
#[GetResource, CanUser('local_field', 'user_field')]
class DirectorResource extends BfgResource
{
    ...
}
```
Will check `resource->local_field == auth->user->user_field`.

If the policy does not fit, it is simply not present in 
the overall list, but reserved in the collection.

## PHP api
To create one instance:
```php
UserResource::make(User::first());
```
To create a collection of instances:
```php
UserResource::collection(User::get());
```
Method for autodetect and create instance for collection or single resource:
```php
UserResource::create(User::first()): static;
// or
UserResource::create(User::get()): BfgResourceCollection;
```

### Inside helpers
- `isPrent()` - Is root nested level.
- `isChild()` - Is child nested level.
- `isNesting(int $needleNested)` - Is nested level equals needle nested.
- `isCollected()` - If the process is a challenge of this resource from the collection.
- `nesting()` - Get nested level.


## PHP Class scope api
To call `scope` In` PHP`, you can use the static constructor:
```php
use App\Http\Resources\UserResource;
...
    // For get resource instance
    UserResource::scope('where', 'name', 'admin', 'first');
    
    // For get resource array result
    UserResource::scope('where', 'name', 'admin', 'first')->toFields();
...
```
You can get the object for use
```php
use App\Http\Resources\UserResource;
...
    // For get resource array result
    UserResource::use('where', 'name', 'admin', 'first'); // object with data
...
```

# Wood support
[wood.veskod.com](https://wood.veskod.com)
