<?php

namespace Bfg\Resource;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BfgResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Create a new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @param  string  $collects
     * @return void
     */
    public function __construct($resource, $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->toFields();
    }

    /**
     * Transform to array fields.
     * @return mixed
     */
    public function toFields()
    {
        return $this->collection->map(fn ($i) => $i->toFields())->toArray();
    }
}
