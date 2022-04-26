<?php

namespace Bfg\Resource\Traits;

use Bfg\Resource\BfgResource;
use Bfg\Resource\BfgResourceCollection;
use Bfg\Resource\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ResourceInitializations
{
    /**
     * Method for apply resource extends
     */
    protected function applyExtends()
    {
        foreach ($this->extends as $class => $only) {
            $numeric = is_numeric($class);
            $class = $numeric ? $only : $class;
            $only = $numeric ? [] : (array)$only;
            if (class_exists($class)) {
                $resourceInstance = new $class($this->resource, 0, $only);
                if ($resourceInstance instanceof BfgResource) {
                    $this->fields = array_merge($this->fields, $resourceInstance->toFields());
                } else if ($resourceInstance instanceof JsonResource) {
                    $this->fields = array_merge($this->fields, $resourceInstance->toArray(request()));
                } else if ($resourceInstance instanceof Collection) {
                    $this->fields = array_merge($this->fields, $resourceInstance->all());
                } else if ($resourceInstance instanceof Arrayable) {
                    $this->fields = array_merge($this->fields, $resourceInstance->toArray());
                }
            }
        }
    }

    /**
     * Make pretty map for generator.
     */
    protected function clearMap()
    {
        $newMap = [];

        foreach ($this->map as $key => $item) {
            $numeric = is_numeric($key);
            $key = $numeric ? $item : $key;
            $item = $numeric ? null : $item;
            $newMap[$key] = $item;
        }

        $this->map = $newMap;
    }

    /**
     * Generate all fields.
     * @return array|void
     * @throws PermissionDeniedException
     */
    protected function generate(array $only)
    {
        $resource_name = Str::snake(str_replace('Resource', '', class_basename(static::class)));

        $check_fields = $this->accessCheck($resource_name);

        if ($check_fields === null) {
            return [];
        }

        $has_only = !!count($only);

        foreach (array_keys($this->map) as $item) {
            $drop_if_null = false;

            if (preg_match('/\?/', $item)) {
                $drop_if_null = true;

                $item = str_replace('?', '', $item);
            }

            if ($has_only && ! in_array($item, $only)) {

                continue ;
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
            } else if ($this->resource->relationLoaded($path)) {
                $resource_result = $this->resource->getRelation($path);
                $relation_collection = $resource_result instanceof Collection;
                $relation_loaded = true;
            } else if (is_array($paginate_params)) {
                $rr = $this->resource->{$name}();

                if ($rr instanceof Relation) {
                    $resource_result = $rr->paginate(...$paginate_params);
                }
            }
        } else {
            $resource_result = $this->resource ?
                multi_dot_call($this->resource, $path ?: $name) : null;
        }

        $camel_name = ucfirst(Str::camel($name));

        $mutator_method = "get{$camel_name}Raw";

        if (method_exists($this, $mutator_method) && !$off_mutators) {
            $resource_result = $this->{$mutator_method}($resource_result??null);
        }

        if (! isset($resource_result)) {
            $resource_result = null;
        }

        if ($resource_class && $this->resource) {
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
}
