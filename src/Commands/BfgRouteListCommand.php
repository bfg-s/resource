<?php

namespace Bfg\Resource\Commands;

use Bfg\Resource\BfgResource;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Str;

class BfgRouteListCommand extends RouteListCommand
{
    /**
     * Get the route information for a given route.
     *
     * @param  Route  $route
     * @return array|object
     * @throws ReflectionException
     */
    protected function getRouteInformation(Route $route)
    {
        $uri = $route->uri();
        $name = $route->getName();
        $action = ltrim($route->getActionName(), '\\');

        if (
            str_ends_with($name, '.scope')
            && str_ends_with($uri, '{scope?}')
            && str_ends_with($action, '@routeAction')
        ) {

            return $this->generateScopeCollection($route);
        }

        return $this->filterRoute([
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $uri,
            'name' => $name,
            'action' => $action,
            'middleware' => $this->getMiddleware($route),
            'vendor' => $this->isVendorRoute($route),
        ]);
    }

    /**
     * @throws ReflectionException
     */
    protected function generateScopeCollection(Route $route): Collection
    {
        $action = Str::parseCallback($route->getActionName())[0];
        $ref = new ReflectionClass($action);
        $methods = $ref->getMethods(ReflectionMethod::IS_STATIC);
        return collect($methods)
            ->filter(
                fn (ReflectionMethod $m)
                => $m->isPublic()
                    && ! method_exists(BfgResource::class, $m->getName())
            )->map(
                fn (ReflectionMethod $m) => $this->generateScopesForMethods($m, $route)
            )->filter();
    }

    protected function generateScopesForMethods(ReflectionMethod $method, Route $route): ?array
    {
        $uri = $route->uri();
        $action = ltrim($route->getActionName(), '\\');
        $methodName = implode('|', $route->methods());

        if (preg_match('/(.+)(Get|Post|Put|Patch|Delete|Options)Scope/', $method->getName(), $m)) {
            $methodName = strtoupper($m[2]);
            $uri = str_replace('{scope?}', Str::snake($m[1]), $uri)
                . $this->makeParamsFromMethod($method);
        } else if (preg_match('/(get|post|put|patch|delete|options)Scope/', $method->getName(), $m)) {
            $methodName = strtoupper($m[1]);
            $uri = str_replace('/{scope?}', '', $uri)
                . $this->makeParamsFromMethod($method);
        } else if (preg_match('/(.+)Scope/', $method->getName(), $m)) {
            $uri = str_replace('{scope?}', Str::snake($m[1]), $uri)
                . $this->makeParamsFromMethod($method);
        } else if (preg_match('/(get|post|put|patch|delete|options)Method/', $method->getName(), $m)) {
            $methodName = strtoupper($m[1]);
        }

        return $this->filterRoute([
            'domain' => $route->domain(),
            'method' => $methodName,
            'uri' => $uri,
            'name' => '',
            'action' => $action,
            'middleware' => $this->getMiddleware($route),
            'vendor' => $this->isVendorRoute($route),
        ]);
    }

    protected function makeParamsFromMethod(ReflectionMethod $method): string
    {
        $q = "";
        foreach ($method->getParameters() as $key => $parameter) {
            if (
                $key
            ) {
                $q .= ($q?"/{":"{")
                    . $parameter->getName()
                    . ($parameter->allowsNull() || $parameter->isOptional() ? "?":"")
                    . "}";
            }
        }
        return $q ? "/$q" : "";
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getRoutes(): array
    {
        $routes = collect();
        $apiRoutes = collect();

        foreach ($this->router->getRoutes() as $route) {

            $result = $this->getRouteInformation($route);

            if ($result instanceof Collection) {

                $apiRoutes = $apiRoutes->merge($result);

            } else if ($result && is_array($result)) {

                $routes->push($result);
            }
        }

        $routes = $routes->filter()->all();

        $apiRoutes = $apiRoutes->filter()->groupBy('uri')
            ->map(fn (Collection $r) => array_merge($r->first(), [
                'method' => $r->pluck('method')->implode('|')
            ]))
            ->values()
            ->all();

        $routes = array_merge($routes, $apiRoutes);

        if (($sort = $this->option('sort')) !== null) {
            $routes = $this->sortRoutes($sort, $routes);
        } else {
            $routes = $this->sortRoutes('uri', $routes);
        }

        if ($this->option('reverse')) {
            $routes = array_reverse($routes);
        }

        return $this->pluckColumns($routes);
    }
}
