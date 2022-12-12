<?php

namespace Bfg\Resource\Wood;

use Bfg\Comcode\Subjects\ClassSubject;
use Bfg\Resource\Wood\Generators\BfgResourceGenerator;
use Bfg\Wood\ClassFactory;
use Bfg\Wood\Models\Model;
use Bfg\Wood\Models\Request;
use Bfg\Wood\ModelTopic;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Bfg\Wood\Models\Resource
 *
 * @property int $id
 * @property Model $model
 * @property bool $http
 * @property bool $all_scope
 * @property bool $find_scope
 * @property bool $first_scope
 * @property bool $latest_scope
 * @property bool $limit_scope
 * @property bool $order_by_scope
 * @property bool $paginate_scope
 * @property bool $random_scope
 * @property bool $skip_scope
 * @property bool $where_scope
 * @property bool $width_scope
 * @property ClassSubject $class
 * @property ClassSubject|null $class_http
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class BfgResource extends ModelTopic
{
    /**
     * @var array|string[]
     */
    protected static array $generators = [
        BfgResourceGenerator::class,
    ];

    /**
     * @var string
     */
    public string $modelIcon = 'fab fa-resolving';

    /**
     * @var string|null
     */
    public ?string $modelName = 'BFG Resources';

    /**
     * @var string|null
     */
    public ?string $modelDescription = 'The bfg resources';

    /**
     * @var array
     */
    public static array $schema = [
        'model' => [
            'select' => 'class',
            'info' => 'Model for resource'
        ],
        'http' => [
            'bool',
            'default' => false,
            'info' => 'Create http resource',
        ],
        'all_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
            'invisible' => true,
        ],
        'find_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'first_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'latest_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'limit_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'order_by_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'paginate_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'random_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'skip_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'where_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
        'width_scope' => [
            'bool',
            'default' => false,
            'if_not' => 'http',
        ],
    ];

    /**
     * @return HasOne
     */
    public function model(): HasOne
    {
        return $this->hasOne(Model::class, 'id', 'model_id');
    }

    /**
     * @return HasOne
     */
    public function store_request(): HasOne
    {
        return $this->hasOne(Request::class, 'id', 'store_request_id');
    }

    public function getClassAttribute()
    {
        $model = Str::singular(class_basename($this->model->class->class));
        return app(ClassFactory::class)
            ->class("App\\Resources\\{$model}Resource", $this);
    }

    public function getClassHttpAttribute()
    {
        if ($this->http) {

            $model = Str::plural(class_basename($this->model->class->class));
            return app(ClassFactory::class)
                ->class("App\\Resources\\Http\\{$model}Resource");
        }
        return null;
    }
}
