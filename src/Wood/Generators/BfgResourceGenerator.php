<?php

namespace Bfg\Resource\Wood\Generators;

use Bfg\Comcode\Comcode;
use Bfg\Comcode\Subjects\DocSubject;
use Bfg\Resource\Traits\Eloquent\EloquentAllScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentFindScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentFirstScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentLatestScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentLimitScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentOrderByScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentPaginateScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentRandomScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentSkipScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentWhereScopeTrait;
use Bfg\Resource\Traits\Eloquent\EloquentWithScopeTrait;
use Bfg\Resource\Wood\BfgResource;
use Bfg\Wood\Generators\GeneratorAbstract;
use Bfg\Wood\Models\Resource;
use Bfg\Wood\Models\Topic;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @mixin BfgResource
 */
class BfgResourceGenerator extends GeneratorAbstract
{
    /**
     * Collection of topics
     * @return Topic[]|Collection|array
     */
    protected function collection(): Collection|array
    {
        return BfgResource::all();
    }

    protected function extends()
    {
        $this->class->extends(
            \Bfg\Resource\BfgResource::class
        );
        $this->class_http?->extends(
            \Bfg\Resource\BfgResource::class
        );
    }

    protected function model()
    {
        $this->class->publicStaticProperty(
            'model',
            Comcode::useIfClass($this->model->class->class, $this->class)."::class"
        );
    }

    protected function http_extends()
    {
        $this->class_http?->protectedProperty(
            ['array', 'extends'],
            [Comcode::useIfClass($this->class->class, $this->class_http)."::class"]
        );
    }

    protected function http_traits()
    {
        if ($this->all_scope) {
            $this->class_http?->trait(EloquentAllScopeTrait::class);
        }
        if ($this->find_scope) {
            $this->class_http?->trait(EloquentFindScopeTrait::class);
        }
        if ($this->first_scope) {
            $this->class_http?->trait(EloquentFirstScopeTrait::class);
        }
        if ($this->latest_scope) {
            $this->class_http?->trait(EloquentLatestScopeTrait::class);
        }
        if ($this->limit_scope) {
            $this->class_http?->trait(EloquentLimitScopeTrait::class);
        }
        if ($this->order_by_scope) {
            $this->class_http?->trait(EloquentOrderByScopeTrait::class);
        }
        if ($this->paginate_scope) {
            $this->class_http?->trait(EloquentPaginateScopeTrait::class);
        }
        if ($this->random_scope) {
            $this->class_http?->trait(EloquentRandomScopeTrait::class);
        }
        if ($this->skip_scope) {
            $this->class_http?->trait(EloquentSkipScopeTrait::class);
        }
        if ($this->where_scope) {
            $this->class_http?->trait(EloquentWhereScopeTrait::class);
        }
        if ($this->width_scope) {
            $this->class_http?->trait(EloquentWithScopeTrait::class);
        }
    }

    protected function map()
    {
        $fields = ['id'];

        $names = $this->model->fields()
            ->where('hidden', false)
            ->pluck('name')
            ->toArray();

        if ($this->model->created) {
            $names[] = 'created_at';
        }
        if ($this->model->updated) {
            $names[] = 'updated_at';
        }
        if ($this->model->deleted) {
            $names[] = '?deleted_at';
        }

        foreach ($this->model->relations as $relation) {
            $relatedResource = BfgResource::where('model_id', $relation->related_model_id)
                ->first();
            if ($relatedResource) {
                $names[$relation->name]
                    = Comcode::useIfClass($relatedResource->class->class, $this->class)."::class";
            }
        }

        $this->class->protectedProperty(
            ['array', 'map'],
            array_merge($fields, $names)
        );
    }

    public function afterSave(): void
    {
        /** @var BfgResource $resource */
        foreach ($this->collection() as $resource) {
            $classContent = $resource->class->content();
            if ($class_http = $resource->class_http) {
                $baseName = class_basename($class_http->class);
                $content = $class_http->content();
                if (! str_contains($content, 'GetResource]')) {

                    $content = str_replace(
                        "class " .  $baseName,
                        "#[\Bfg\Resource\Attributes\GetResource]\n" . "class " .  $baseName,
                        $content
                    );
                    file_put_contents($class_http->fileSubject->file, $content);
                }
                $classContent = preg_replace('/\d+\s=>\s/m', '', $classContent);
                file_put_contents($resource->class->fileSubject->file, $classContent);
            }
        }
    }

//    protected function toArray()
//    {
//        $this->class->use(Arrayable::class);
//        $this->class->use(JsonSerializable::class);
//        $this->class->when(
//            $this->class->notExistsMethod('toArray'),
//            fn() => $this->class
//                ->publicMethod('toArray')
//                ->expectParams('request')
//                ->comment(
//                    fn (DocSubject $doc)
//                    => $doc->name('Transform the resource into an array.')
//                        ->tagParam(Request::class, 'request')
//                        ->tagReturn('array|Arrayable|JsonSerializable')
//                )
//                ->return()
//                ->staticCall('parent', 'toArray', php('request'))
//        );
//    }
}
