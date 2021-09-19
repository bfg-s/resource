<?php

namespace Bfg\Resource;

use Bfg\Resource\Attributes\CanScope;
use Bfg\Resource\Exceptions\PermissionDeniedException;
use Bfg\Resource\Exceptions\ResourceException;
use Bfg\Resource\Exceptions\UndefinedScopeException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Controller
{
    /**
     * @param  BfgResource|string  $resource
     * @return mixed
     * @throws \Throwable
     */
    public function index($resource): mixed
    {
        try {
            $result = $this->buildDefaultResource($resource);
        } catch (\Throwable $exception) {
            return $this->buildException($exception);
        }

        return $result ? ($result instanceof Collection || $result instanceof LengthAwarePaginator ?
            $resource::collection($result) : $resource::make($result)) : [];
    }

    /**
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildException(\Throwable $exception): \Illuminate\Http\JsonResponse
    {
        if (config('app.debug')) {
            \Log::error($exception);
            if (!($exception instanceof ResourceException)) {
                return response()->json([
                    'status' => 'error',
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage() ?: 'Bad Request',
                    'trace' => $exception->getTrace()
                ], $exception->getCode() ?: 400);
            }
            return response()->json([
                'status' => 'error',
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ], $exception->getCode());
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Bad Request',
        ], $exception->getCode() ?: 400);
    }

    /**
     * Build requests for default resource data.
     * @param  BfgResource|string  $resource
     * @return mixed
     * @throws \Throwable
     */
    protected function buildDefaultResource($resource): mixed
    {
        return $this->applyScopes(
            $resource,
            $resource::getDefaultResource()
        );
    }

    /**
     * @param  string  $resource
     * @param $result
     * @return mixed
     * @throws \Throwable
     */
    protected function applyScopes(string $resource, $result): mixed
    {
        $scope = request('scope');

        if (method_exists($resource, 'globalScope')) {
            $result = embedded_call([$resource, 'globalScope'], [$result]);
        }

        return $scope ? $this->callScopes(
            $this->sortScopes($scope, $resource), $resource, $result
        ) : $result;
    }

    /**
     * @param  array  $callScopes
     * @param $resource
     * @param $result
     * @return mixed
     * @throws \Throwable
     */
    protected function callScopes(array $callScopes, $resource, $result): mixed
    {
        $ref = new  \ReflectionClass($resource);
        $resource_name = \Str::snake(str_replace('Resource', '', class_basename($resource)));
        foreach ($callScopes as $callScope => $scopeParams) {
            $callScope = preg_replace("/[\d]+#(.*)/", '$1', $callScope);
            $this->checkCanScope($ref, $callScope, $resource, $resource_name);
            $call = fn($model) => $this->scopeCaller($model, $callScope, $scopeParams, $resource);
            if ($result instanceof \Illuminate\Database\Eloquent\Collection) {
                return $result->map($call);
            } else {
                if ($result instanceof LengthAwarePaginator) {
                    return $result->setCollection(
                        $result->getCollection()->map($call)
                    );
                } else {
                    $result = $call($result);
                }
            }
        }

        return $result;
    }

    /**
     * @param  \ReflectionClass  $ref
     * @param $callScope
     * @param  string|BfgResource  $resource
     * @param $resource_name
     * @throws \ReflectionException
     * @throws PermissionDeniedException
     */
    protected function checkCanScope(\ReflectionClass $ref, $callScope, $resource, $resource_name)
    {
        $method = $ref->getMethod($callScope);
        $scope_name = str_replace("Scope", "", $callScope);
        $cans = $method->getAttributes(CanScope::class, \ReflectionAttribute::IS_INSTANCEOF);
        foreach ($cans as $can) {
            $can = $can->newInstance();
            if (
                !\Auth::guard($resource::$guard)->check() ||
                !\Auth::guard($resource::$guard)->user()->can(
                    $can->permission ?: $scope_name.'-'.$resource_name
                )
            ) {
                throw new PermissionDeniedException($scope_name);
            }
        }
    }

    /**
     * @param $model
     * @param $callScope
     * @param $scopeParams
     * @param $resource
     * @return mixed
     * @throws \Throwable
     */
    protected function scopeCaller($model, $callScope, $scopeParams, $resource): mixed
    {
        return embedded_call([$resource, $callScope],
            [$model, (array) $scopeParams, ...(array) $scopeParams]);
    }

    /**
     * @param  string  $scope
     * @param  string  $resource
     * @return array
     * @throws \Exception
     */
    protected function sortScopes(string $scope, string $resource): array
    {
        $callScopes = [];
        $scopes = explode('/', $scope);
        $route_method = ucfirst(strtolower(request()->getMethod()));
        foreach ($scopes as $key => $scope) {
            $camel_scope = !is_numeric($scope) ? \Str::camel($scope) : null;
            $name_method = $camel_scope ? "{$camel_scope}{$route_method}Scope" : null;
            if ($camel_scope && !method_exists($resource, $name_method)) {
                $name_method = "{$camel_scope}Scope";
            }
            if ($camel_scope && method_exists($resource, $name_method)) {
                $callScopes["{$key}#".$name_method] = [];
            } else {
                if (count($callScopes)) {
                    $callScopes[array_key_last($callScopes)][] = route_real_param($scope);
                } else {
                    throw new UndefinedScopeException();
                }
            }
        }

        return $callScopes;
    }
}
