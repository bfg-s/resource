<?php

namespace Bfg\Resource;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @template-covariant TResource of \Bfg\Resource\BfgResource
 */
class BfgResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var class-string<TResource>
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var \Illuminate\Support\Collection<int, TResource>
     */
    public $collection;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param  class-string<TResource>|null  $collects
     */
    public function __construct($resource, string $collects = null)
    {
        if ($collects) {
            $this->collects = $collects;
        }

        parent::__construct($resource);
    }

    /**
     * @param  array|string  $name
     * @param  mixed|null  $value
     * @return $this
     */
    public function fill(array|string $name, mixed $value = null): static
    {
        foreach ($this->collection as $item) {
            $item->fill($name, $value);
        }

        return $this;
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<array<string, mixed>>
     */
    public function toArray($request): array
    {
        return $this->toFields();
    }

    /**
     * Transform to array fields.
     *
     * @return array<array<string, mixed>>
     */
    public function toFields(): array
    {
        return $this->collection
            ->map(fn ($i) => $i->toFields())
            ->toArray();
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
        $this->collection->map->set($name, $value);
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
        $this->collection->map->setWith($with);
        $this->with = $with;

        return $this;
    }

    /**
     * Set the errors of the response.
     *
     * @param  object|array<array-key, mixed>  $errors
     * @return $this
     */
    public function setErrors(object|array $errors): static
    {
        if (is_object($errors) && method_exists($errors, 'errors')) {
            $errors = call_user_func([$errors, 'errors'], $this);
        }

        if (count($errors) > 0) {
            $this->with['errors'] = $errors;
        }

        return $this;
    }

    /**
     * Add additional meta data to the response.
     *
     * @param  array<string, mixed>  $meta
     * @return static
     */
    public function setMeta(array $meta): static
    {
        $existing = isset($this->with['meta']) && is_array($this->with['meta']) ? $this->with['meta'] : [];
        $this->with['meta'] = array_merge_recursive($existing, $meta);

        return $this;
    }

    /**
     * Proxy with for first resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function with($request): mixed
    {
        $result = $this->collection->first()?->with($request) ?: $this->with;

        foreach ($this->collection as $item) {

            if (method_exists($item, 'withItem')) {

                $resultWithItem = $item->withItem($request);

                if ($resultWithItem && is_array($result)) {

                    $result = array_merge($result, $resultWithItem);
                }
            }
        }

        return $result;
    }
}
