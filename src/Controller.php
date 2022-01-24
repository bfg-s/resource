<?php

namespace Bfg\Resource;

use App\Models\User;
use Bfg\Resource\Attributes\CanScope;
use Bfg\Resource\Attributes\CanUser;
use Bfg\Resource\Exceptions\AttemptToCheckBuilderException;
use Bfg\Resource\Exceptions\PermissionDeniedException;
use Bfg\Resource\Exceptions\ResourceException;
use Bfg\Resource\Exceptions\UndefinedScopeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Controller
{
    /**
     * @var string|null
     */
    protected ?string $scope = null;

    /**
     * @var User|null
     */
    public static ?User $user = null;

    /**
     * @param  BfgResource|string  $resource
     * @param  string|null  $scope
     * @return mixed
     * @throws PermissionDeniedException
     * @throws \Throwable
     */
    public function index(BfgResource|string $resource, string $scope = null): mixed
    {
        $this->scope = $scope ?: request('scope');

        try {
            $result = $this->buildDefaultResource($resource);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            \Log::error($exception);

            return $this->buildException($exception);
        }

        if ($result instanceof Builder) {
            $route_method = strtolower(request()->getMethod()).'Method';
            if (method_exists($resource, $route_method)) {
                $result = embedded_call([$resource, $route_method], [$result]);
            }
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
        $code = 400;

        if (config('app.debug')) {
            if (! ($exception instanceof ResourceException)) {
                return response()->json([
                    'status' => 'error',
                    'code' => $exception->getCode(),
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage() ?: 'Bad Request',
                    'trace' => $exception->getTrace(),
                ], $code);
            }

            return response()->json([
                'status' => 'error',
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ], $code);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Bad Request',
        ], $code);
    }

    /**
     * Build requests for default resource data.
     * @param  BfgResource|string  $resource
     * @return mixed
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function buildDefaultResource(BfgResource|string $resource): mixed
    {
        return $this->applyScopes(
            $resource,
            $resource::getDefaultResource()
        );
    }

    /**
     * @param  BfgResource|string  $resource
     * @param $result
     * @return mixed
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    protected function applyScopes(BfgResource|string $resource, $result): mixed
    {
        $sortedScopes = $this->scope ? static::sortScopes($this->scope, $resource) : [];
        if (method_exists($resource, 'globalScope')) {
            $result = embedded_call([$resource, 'globalScope'], [
                $result, \Arr::last($sortedScopes['globalScope'])
            ]);
        }

        if (!count($sortedScopes)) {
            throw new UndefinedScopeException('any');
        }

        return $this->scope ? static::callScopes(
            $sortedScopes, $resource, $result
        ) : $result;
    }

    /**
     * @param  array  $callScopes
     * @param  BfgResource|string  $resource
     * @param $result
     * @return mixed
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function callScopes(array $callScopes, BfgResource|string $resource, $result): mixed
    {
        $ref = new \ReflectionClass($resource);
        $resource_name = \Str::snake(str_replace('Resource', '', class_basename($resource)));
        foreach ($callScopes as $callScope => $scopeParams) {
            $callScope = preg_replace("/[\d]+#(.*)/", '$1', $callScope);
            static::checkCanScope($ref, $callScope, $resource, $resource_name);
            $call = fn ($model) => static::scopeCaller($model, $callScope, $scopeParams, $resource, $ref);
            if ($result instanceof Collection) {
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
     * @param $resource
     * @return User|null
     */
    protected static function user($resource): ?User
    {
        if (! static::$user) {
            static::$user = \Auth::guard($resource::$guard)->user();
        }

        return static::$user;
    }

    /**
     * @param  \ReflectionClass  $ref
     * @param $callScope
     * @param  string|BfgResource  $resource
     * @param $resource_name
     * @throws PermissionDeniedException
     * @throws \ReflectionException
     */
    public static function checkCanScope(\ReflectionClass $ref, $callScope, BfgResource|string $resource, $resource_name)
    {
        $method = $ref->getMethod($callScope);
        $scope_name = str_replace('Scope', '', $callScope);
        $cans = $method->getAttributes(CanScope::class, \ReflectionAttribute::IS_INSTANCEOF);
        foreach ($cans as $can) {
            $can = $can->newInstance();
            if (
                ! \Auth::guard($resource::$guard)->check() ||
                ! \Auth::guard($resource::$guard)->user()->can(
                    $can->permission ?: $scope_name.'-'.$resource_name
                )
            ) {
                throw new PermissionDeniedException($scope_name);
            }
        }
        $cans = $method->getAttributes(CanUser::class, \ReflectionAttribute::IS_INSTANCEOF);
    }

    /**
     * For call scope method.
     * @param $model
     * @param $callScope
     * @param $scopeParams
     * @param BfgResource|string $resource
     * @param  \ReflectionClass  $ref
     * @return mixed
     * @throws AttemptToCheckBuilderException
     * @throws PermissionDeniedException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function scopeCaller($model, $callScope, $scopeParams, BfgResource|string $resource, \ReflectionClass $ref): mixed
    {
        $method = $ref->getMethod($callScope);
        $cans = $method->getAttributes(CanUser::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($cans) {
            if ($model instanceof Builder) {
                throw new AttemptToCheckBuilderException();
            }
            foreach ($cans as $can) {
                /** @var CanUser $attr */
                $attribute = $can->newInstance();
                if (
                    multi_dot_call(static::user($resource), $attribute->user_field) !=
                    multi_dot_call($model, $attribute->local_field)
                ) {
                    throw new PermissionDeniedException($callScope);
                }
            }
        }

        return embedded_call([$resource, $callScope],
            [$model, ...(array) $scopeParams]);
    }

    /**
     * @param  string  $scope
     * @param  BfgResource|string  $resource
     * @return array
     * @throws UndefinedScopeException
     */
    public static function sortScopes(string $scope, BfgResource|string $resource): array
    {
        $callScopes = [];
        $scopes = explode('/', $scope);
        $route_method = ucfirst(strtolower(request()->getMethod()));
        foreach ($scopes as $key => $scope) {
            $camel_scope = ! is_numeric($scope) ? \Str::camel($scope) : null;
            $name_method = $camel_scope ? "{$camel_scope}{$route_method}Scope" : null;
            if ($camel_scope && ! method_exists($resource, $name_method)) {
                $name_method = "{$camel_scope}CollectionScope";
            }
            if ($camel_scope && ! method_exists($resource, $name_method)) {
                $name_method = "{$camel_scope}Scope";
            }
            if ($camel_scope && method_exists($resource, $name_method)) {
                $callScopes["{$key}#".$name_method] = [];
            } else {
                $routeRealParam = route_real_param($scope);
                if (count($callScopes)) {
                    $callScopes[array_key_last($callScopes)][] = $routeRealParam;
                } else if ($routeRealParam) {
                    $callScopes['globalScope'][] = $routeRealParam;
                } else {
                    throw new UndefinedScopeException($scope);
                }
            }
        }

        return $callScopes;
    }
}
