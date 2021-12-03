<?php

namespace Bfg\Resource;

use App\Models\User;
use Bfg\Resource\Attributes\CanFields;
use Bfg\Resource\Attributes\CanResource;
use Bfg\Resource\Attributes\CanUser;
use Bfg\Resource\Exceptions\PermissionDeniedException;
use Bfg\Resource\Traits\ResourceClassApiTrait;
use Bfg\Resource\Traits\ResourceRoutingTrait;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Date;

class BfgResource extends JsonResource
{
    use ResourceRoutingTrait,
        ResourceClassApiTrait;

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
     * @param $resource
     * @throws PermissionDeniedException
     */
    public function __construct($resource = null)
    {
        parent::__construct($resource);

        if ($resource !== null) {
            $this->clearMap();

            $this->generate();
        }
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
     * Generate all fields.
     * @return array|void
     * @throws PermissionDeniedException
     */
    protected function generate()
    {
        $resource_name = \Str::snake(str_replace('Resource', '', class_basename(static::class)));

        $check_fields = $this->accessCheck($resource_name);

        if ($check_fields === null) {
            return [];
        }

        foreach (array_keys($this->map) as $item) {
            $drop_if_null = false;

            if (preg_match('/\?/', $item)) {
                $drop_if_null = true;

                $item = str_replace('?', '', $item);
            }

            $add = true;
            if (array_key_exists($item, $check_fields)) {
                if (! $this->user()->can(
                    $check_fields[$item] ?: $item.'-field-'.$resource_name
                )) {
                    $add = false;
                }
            }
            if ($add) {
                $this->generateField(
                    $item,
                    array_key_exists($item, $this->temporal) || in_array($item,
                        $this->temporal) || $drop_if_null || $this->temporal_all
                );
            }
        }
    }

    /**
     * @param  string  $name
     * @param  bool  $drop_if_null
     * @return mixed
     * @throws PermissionDeniedException
     */
    protected function generateField(string $name, bool $drop_if_null = false): mixed
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        $resource_class = array_key_exists($name, $this->map) ? $this->map[$name] : null;

        $path = null;

        $paginate_params = null;

        if (is_array($resource_class)) {
            if (isset($resource_class['paginate']) && is_array($resource_class['paginate'])) {
                $paginate_params = $resource_class['paginate'];

                unset($resource_class['paginate']);

                $resource_class = array_values($resource_class);
            }

            if (isset($resource_class[0]) && isset($resource_class[1])) {
                $path = $resource_class[1];

                $resource_class = $resource_class[0];
            } elseif (isset($resource_class[0])) {
                $path = $resource_class[0];
            } else {
                $resource_class = null;
            }
        }

        if ($resource_class && ! $path && is_string($resource_class)) {
            if (! class_exists($resource_class)) {
                $path = $resource_class;

                $resource_class = null;
            }
        }

        $relation_loaded = false;

        $relation_collection = false;

        if ($resource_class && $this->resource instanceof Model) {
            if ($this->resource->relationLoaded($name)) {
                $resource_result = $this->resource->getRelation($name);

                $relation_collection = $resource_result instanceof Collection;

                $relation_loaded = true;
            } elseif (is_array($paginate_params)) {
                $rr = $this->resource->{$name}();

                if ($rr instanceof Relation) {
                    $resource_result = $rr->paginate(...$paginate_params);
                }
            }
        } else {
            $resource_result = $this->resource ?
                multi_dot_call($this->resource, $path ?: $name) : null;
        }

        $camel_name = ucfirst(\Str::camel($name));

        $mutator_method = "get{$camel_name}Field";

        if (method_exists($this, $mutator_method)) {
            $resource_result = $this->{$mutator_method}($resource_result);
        }

        if (! isset($resource_result)) {
            $resource_result = null;
        }

        if ($resource_class && $this->resource) {
            if (isset(static::$created[$resource_class])) {
                static::$created[$resource_class] += 1;
            } else {
                static::$created[$resource_class] = 0;
            }
            if ($resource_result instanceof Collection || $resource_result instanceof LengthAwarePaginator) {
                $this->fields[$name] = $resource_result = tap(new BfgResourceCollection($resource_result,
                    $resource_class), function ($collection) use ($resource_class) {
                    if (property_exists($resource_class, 'preserveKeys')) {
                        $collection->preserveKeys = (new static([]))->preserveKeys === true;
                    }
                });
            } elseif ($resource_result) {
                $this->fields[$name] = $resource_result = new $resource_class($resource_result);
            }
        } else {
            $this->fields[$name] = $resource_result = $this->fieldCasting($name, $resource_result);
        }

        if ($relation_loaded && ! isset($this->fields[$name]) && ! $drop_if_null) {
            $this->fields[$name] = $relation_collection ? [] : null;
        } elseif ($drop_if_null && array_key_exists($name, $this->fields) && is_null($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $resource_result;
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
                    multi_dot_call($this->user(), $attribute->user_field) !=
                    multi_dot_call($this->resource, $attribute->local_field)
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
     * Make pretty map for generator.
     */
    protected function clearMap()
    {
        $newMap = [];

        foreach ($this->map as $key => $item) {
            if (is_numeric($key)) {
                $newMap[$item] = null;
            } else {
                $newMap[$key] = $item;
            }
        }

        $this->map = $newMap;
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
        }

        return $fields;
    }

    /**
     * @param  string  $name
     * @param $value
     * @return mixed
     */
    protected function fieldCasting(string $name, $value): mixed
    {
        if (isset($this->casts[$name])) {
            $castType = $this->casts[$name];

            switch ($castType) {
                case 'int':
                case 'integer':
                    return (int) $value;
                case 'real':
                case 'float':
                case 'double':
                    return $this->fromFloat($value);
                case 'decimal':
                    return $this->asDecimal($value, explode(':', $this->casts[$name], 2)[1]);
                case 'string':
                    return (string) $value;
                case 'bool':
                case 'boolean':
                    return (bool) $value;
                case 'object':
                    return $this->fromJson($value, true);
                case 'array':
                case 'json':
                    return $this->fromJson($value);
                case 'collection':
                    return new BaseCollection($this->fromJson($value));
                case 'date':
                    return $this->asDate($value);
                case 'datetime':
                case 'custom_datetime':
                    return $this->asDateTime($value);
                case 'timestamp':
                    return $this->asTimestamp($value);
            }

            if (is_string($castType) && class_exists($castType)) {
                return $this->getClassCastableAttributeValue($name, $value);
            }
        }

        return $value;
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string|null  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson(?string $value, bool $asObject = false): mixed
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return int|float
     */
    public function fromFloat(mixed $value): int|float
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Return a decimal as string.
     *
     * @param  float|string  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal(float|string $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDate(mixed $value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return bool|Carbon
     */
    protected function asDateTime(mixed $value): bool|Carbon
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof \DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        $format = $this->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (\InvalidArgumentException $e) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp(mixed $value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Cast the given attribute using a custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getClassCastableAttributeValue(string $key, mixed $value): mixed
    {
        if (isset($this->classCastCache[$key])) {
            return $this->classCastCache[$key];
        } else {
            $caster = $this->resolveCasterClass($key);

            $value = $caster instanceof CastsInboundAttributes
                ? $value
                : $caster->get($this, $key, $value, $this->fields);

            if ($caster instanceof CastsInboundAttributes || ! is_object($value)) {
                unset($this->classCastCache[$key]);
            } else {
                $this->classCastCache[$key] = $value;
            }

            return $value;
        }
    }

    /**
     * Resolve the custom caster class for a given key.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function resolveCasterClass(string $key): mixed
    {
        $castType = $this->casts[$key];

        $arguments = [];

        if (is_string($castType) && str_contains($castType, ':')) {
            $segments = explode(':', $castType, 2);

            $castType = $segments[0];
            $arguments = explode(',', $segments[1]);
        }

        if (is_subclass_of($castType, Castable::class)) {
            $castType = $castType::castUsing($arguments);
        }

        if (is_object($castType)) {
            return $castType;
        }

        return new $castType(...$arguments);
    }

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return BfgResourceCollection
     * @throws PermissionDeniedException
     */
    public static function collection($resource): BfgResourceCollection
    {
        return tap(new BfgResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
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
     * Get nested level
     * @return int
     */
    public function nesting(): int
    {
        return static::$created[$this::class] ?? 0;
    }
}
