<?php

namespace Bfg\Resource;

use Bfg\Repository\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BfgResourceRequest extends BfgResource
{
    abstract public function repository(): string;

    public function createRequest(): string|null
    {
        return null;
    }

    public function updateRequest(): string|null
    {
        return null;
    }

    public function index(): BfgResourceCollection|JsonResponse
    {
        $repository = app($this->repository());

        return $repository instanceof Repository ? $repository
            ->resource(static::class)
            ->get() : response()->json([]);
    }

    public function store(): BfgResource|null
    {
        $request = app($this->createRequest() ?: \Illuminate\Http\Request::class);
        $repository = app($this->repository());

        $data = $this->createRequest() ? $request->validated() : $request->all();

        return $repository instanceof Repository ? $repository
            ->resource(static::class)
            ->create($data) : null;
    }

    public function show(Request $request)
    {
        $repository = app($this->repository());
        $first = array_key_first($request->route()->parameters());

        return $repository instanceof Repository ? $repository
            ->setModel($repository->model()->find($request->{$first}) ?: abort(404))
            ->resource(static::class)
            ->wrap() : null;
    }

    public function update()
    {
        $request = app($this->updateRequest() ?: \Illuminate\Http\Request::class);
        $repository = app($this->repository());

        $data = $this->updateRequest() ? $request->validated() : $request->all();

        return $repository instanceof Repository ? $repository
            ->setModel($repository->model()->find($request->{$first}) ?: abort(404))
            ->resource(static::class)
            ->update($data) : null;
    }

    public function destroy(Request $request)
    {
        $repository = app($this->repository());

        return $repository instanceof Repository ? $repository
            ->setModel($repository->model()->find($request->{$first}) ?: abort(404))
            ->resource(static::class)
            ->delete() : null;
    }
}
