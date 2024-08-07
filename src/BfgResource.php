<?php

namespace Bfg\Resource;

use App\Models\User;
use Bfg\Resource\Attributes\CanFields;
use Bfg\Resource\Attributes\CanResource;
use Bfg\Resource\Attributes\CanUser;
use Bfg\Resource\Traits\ResourceClassApiTrait;
use Bfg\Resource\Traits\ResourceInitializations;
use Bfg\Resource\Traits\ResourceRoutingTrait;
use Bfg\Resource\Traits\ResourceCasting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class BfgResource extends JsonResource
{
    use ResourceRoutingTrait,
        ResourceClassApiTrait,
        ResourceCasting,
        ResourceInitializations;

    /**
     * The default resource.
     * @var mixed|null
     */
    public static $model = null;

    /**
     * @var string
     */
    public static $guard = 'api';

    /**
     * @var User|null
     */
    public static $user = null;

    /**
     * @var array|string[]
     */
    public static array $created = [];

    /**
     * @var int
     */
    public int $nesting = 0;

    /**
     * @var int|null
     */
    public ?int $index = null;

    /**
     * Map of resource fields.
     * @var array
     */
    protected array $map = [];

    /**
     * Created resource fields.
     * @var array
     */
    protected array $fields = [];

    /**
     * The casts of fields.
     * @var array
     */
    protected array $casts = [];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * The attributes that have been cast using custom classes.
     *
     * @var array
     */
    protected array $classCastCache = [];

    /**
     * Temporary variables that will not fall into the result if null.
     *
     * @var array
     */
    protected array $temporal = [];

    /**
     * Remove all parameters that are null.
     * @var bool
     */
    protected bool $temporal_all = false;

    /**
     * Combines the results of resource fields.
     * Performed before the main resource for redefining the parent.
     * @var array
     */
    protected array $extends = [];

    /**
     * Force load and include relations.
     * @var array
     */
    protected array $includes = [];

    /**
     * @param  null  $resource
     * @param  int|null  $index
     * @param  array  $only More is needed for embedded resource extensions.
     */
    public function __construct($resource = null, ?int $index = null, array $only = [])
    {
        $this->index = $index;

        if ($resource !== null) {
            if (isset(static::$created[static::class])) {
                static::$created[static::class] += 1;
            } else {
                static::$created[static::class] = 0;
            }
        }

        parent::__construct($resource);

        if ($resource !== null) {
            $this->generateMap();
            $this->clearMap();
            $this->applyExtends();
            $this->generate($only);
        }
    }

    /**
     * Function for map generating before the main resource.
     *
     * @return void
     */
    protected function generateMap()
    {

    }

    /**
     * @return User|null
     */
    protected function user(): ?User
    {
        if (! static::$user) {
            static::$user = \Auth::guard(static::$guard)->user();
        }

        return static::$user;
    }

    /**
     * @param $resource_name
     * @return array|null
     */
    protected function accessCheck($resource_name): ?array
    {
        $ref = new \ReflectionClass(static::class);

        $map_ref = $ref->getProperty('map');

        $attributes = $ref->getAttributes(CanResource::class, \ReflectionAttribute::IS_INSTANCEOF);

        $attributes_user = $ref->getAttributes(CanUser::class, \ReflectionAttribute::IS_INSTANCEOF);

        $map_attributes = $map_ref->getAttributes(CanFields::class, \ReflectionAttribute::IS_INSTANCEOF);

        $check_fields = [];

        if ($attributes || $attributes_user || $map_attributes) {
            if (! $this->user()) {
                return null;
            }
        }

        if ($attributes) {
            foreach ($map_attributes as $attribute) {
                $attribute = $attribute->newInstance();
                /** @var CanResource $attribute */
                if (! $this->user()->can(
                    $attribute->permission ?: $resource_name
                )) {
                    return null;
                }
            }
        }

        if ($attributes_user) {
            foreach ($attributes_user as $attribute) {
                $attribute = $attribute->newInstance();
                /** @var CanUser $attribute */
                if (
                    static::multiDotCall($this->user(), $attribute->user_field) !=
                    static::multiDotCall($this->resource, $attribute->local_field)
                ) {
                    return null;
                }
            }
        }

        if ($map_attributes) {
            foreach ($map_attributes as $attribute) {
                $attribute = $attribute->newInstance();
                $check_fields = array_merge($check_fields, $attribute->fields);
            }
        }

        return $check_fields;
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->toFields();
    }

    /**
     * @return array
     */
    public function toFields(): array
    {
        $fields = [];

        $request = request();

        foreach ($this->fields as $key => $field) {
            if ($field instanceof JsonResource) {
                $fields[$key] = $field->toArray($request);
            } else {
                $fields[$key] = $field;
            }

            $camel_name = ucfirst(Str::camel($key));

            $mutator_method = "get{$camel_name}Field";

            if (method_exists($this, $mutator_method)) {
                $fields[$key] = $this->{$mutator_method}($fields[$key]);
            }
        }

        return $fields;
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return BfgResourceCollection
     */
    public static function collection($resource): BfgResourceCollection
    {
        return tap(new BfgResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Create a new resource instance.
     *
     * @param  mixed  ...$parameters
     * @return static
     */
    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }

    /**
     * Method for autodetect and create instance for collection or single resource.
     * @param $resource
     * @return BfgResourceCollection|static
     */
    public static function create($resource): BfgResourceCollection|static
    {
        if (
            $resource instanceof Collection
            || $resource instanceof LengthAwarePaginator
            || (is_array($resource) && !static::isAssoc($resource))
        ) {
            return static::collection($resource);
        }
        return static::make($resource);
    }

    public static function getResource(): mixed
    {
        return static::$model;
    }

    /**
     * Getter function for cutomize.
     * @return mixed
     */
    public static function getDefaultResource(): mixed
    {
        $resource = static::getResource();

        if (! $resource) {
            $class_name = class_basename(static::class);
            $class_name = preg_replace('/(.*)Resource$/', '$1', $class_name);
            $model_find = "App\\Models\\{$class_name}";
            if (class_exists($model_find)) {
                $resource = $model_find;
            }
        }

        $model = is_string($resource) ? app($resource) : $resource;

        if ($model instanceof Model) {
            $model = $model::query();
        }

        return $model;
    }

    /**
     * @param  array|string  $name
     * @param  mixed|null  $value
     * @return $this
     */
    public function fill(array|string $name, mixed $value = null): static
    {
        if (is_array($name)) {

            foreach ($name as $key => $item) {

                $this->fill($key, $item);
            }

            return $this;
        }

        $this->__set($name, $value);

        return $this;
    }

    /**
     * Determine if an attribute exists on the resource.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        if (is_object($this->resource)) {
            return isset($this->resource->{$key});
        } else {
            if (is_array($this->resource)) {
                return array_key_exists($key, $this->resource);
            }
        }

        return false;
    }

    /**
     * Unset an attribute on the resource.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        if (is_object($this->resource)) {
            unset($this->resource->{$key});
        } else {
            if (is_array($this->resource)) {
                unset($this->resource[$key]);
            }
        }
    }

    public function __set(string $name, $value): void
    {
        if (is_object($this->resource)) {
            $this->resource->{$name} = $value;
        } else {
            if (is_array($this->resource)) {
                $this->resource[$name] = $value;
            }
        }
    }

    /**
     * Dynamically get properties from the underlying resource.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (is_object($this->resource)) {
            return $this->resource->{$key};
        } else {
            if (is_array($this->resource)) {
                return $this->resource[$key];
            }
        }

        return null;
    }

    /**
     * Dynamically pass method calls to the underlying resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (is_object($this->resource)) {
            return $this->forwardCallTo($this->resource, $method, $parameters);
        } else {
            if (is_array($this->resource)) {
                return $this->resource[$method] instanceof \Closure ? $this->resource[$method](...$parameters) : null;
            }
        }

        return null;
    }

    /**
     * Helpers
     */

    /**
     * Is root nested level
     * @return bool
     */
    public function isPrent(): bool
    {
        return static::$created[$this::class] === 0;
    }

    /**
     * Is child nested level
     * @return bool
     */
    public function isChild(): bool
    {
        return static::$created[$this::class] > 0;
    }

    /**
     * Is nested level equals needle nested
     * @param  int  $needleNested
     * @return bool
     */
    public function isNesting(int $needleNested): bool
    {
        return static::$created[$this::class] === $needleNested;
    }

    /**
     * If the process is a challenge of this resource from the collection.
     * @return bool
     */
    public function isCollected(): bool
    {
        return $this->index !== null;
    }

    /**
     * Get nested level
     * @return int
     */
    public function nesting(): int
    {
        return static::$created[$this::class] ?? 0;
    }

    /**
     * Check whether an array is associative.
     *
     * @param  array  $arr
     * @return bool
     */
    public static function isAssoc(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Set a value to the object.
     *
     * @param  string  $name
     * @param  mixed|null  $value
     * @return $this
     */
    public function set(string $name, mixed $value = null): static
    {
        $this->{$name} = $value;

        return $this;
    }

    /**
     * Set with variable
     *
     * @param  array  $with
     * @return $this
     */
    public function setWith(array $with): static
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Access to an object or/and an array using the dot path method.
     *
     * @param $obj
     * @param  string  $dot_path
     * @param  bool  $locale
     * @return mixed
     */
    public static function multiDotCall($obj, string $dot_path, bool $locale = true): mixed
    {
        return \Bfg\Resource\Accessor::create($obj)->dotCall($dot_path, $locale);
    }

    /**
     * Get any additional data that should be returned with each the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function withItem($request)
    {
        return [];
    }
}
