<?php

namespace Bfg\Resource\Commands;

use Illuminate\Foundation\Console\ResourceMakeCommand as IlluminateResourceMakeCommand;
use Symfony\Component\Console\Input\InputOption;

class ResourceMakeCommand extends IlluminateResourceMakeCommand
{
    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceClassDoc($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the doc bloc for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceClassDoc(&$stub, $name): static
    {
        $searches = [
            ['DummyDocBlock', 'DummyUses'],
            ['{{ doc_block }}', '{{ uses }}'],
            ['{{doc_block}}'. '{{uses}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getDocBlock(), $this->getUses()],
                $stub
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getUses()
    {
        $model = $this->model();

        if ($model) {

            $class = class_basename($model);

            $m = $this->option('model');

            $t = $m ? "\n    public static \$model = $class::class;\n":"";

            return <<<DOC
    
    use EloquentScopesTrait, ModelScopesTrait;
{$t}
DOC;
        }
        return "";
    }

    /**
     * @return string
     */
    protected function getDocBlock(): string
    {
        $model = $this->model();

        if ($model) {

            $class = class_basename($model);

            return <<<DOC
use $model;
use Bfg\Resource\Traits\ModelScopesTrait;
use Bfg\Resource\Traits\EloquentScopesTrait;

/**
 * @mixin $class
 */
DOC;
        }
        return "\n";
    }

    protected function model()
    {
        $m = $this->option('model') ? ucfirst(\Str::camel($this->option('model'))) : null;
        $n = ucfirst(\Str::camel(trim($this->argument('name'))));
        $name = preg_replace('/(.*)Resource$/', '$1', $n);
        return $m && class_exists("App\\Models\\{$m}") ? "App\\Models\\{$m}" :
            (class_exists("App\\Models\\{$name}") ? "App\\Models\\{$name}" : null);
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        $n = ucfirst(\Str::camel(trim($this->argument('name'))));
        return preg_replace('/(.*)Resource$/', '$1', $n).'Resource';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Create with the model'],
            ['collection', 'c', InputOption::VALUE_NONE, 'Create a resource collection'],
        ];
    }
}
