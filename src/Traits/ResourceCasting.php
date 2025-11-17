<?php

namespace Bfg\Resource\Traits;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Date;

trait ResourceCasting
{
    /**
     * The built-in, primitive cast types supported by Eloquent.
     *
     * @var string[]
     */
    protected array $__primitiveCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'float',
        'hashed',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'string',
        'timestamp',
    ];

    /**
     * @param  string  $name
     * @param $value
     * @return mixed
     */
    protected function fieldCasting(string $name, $value): mixed
    {
        if (isset($this->casts[$name])) {
            $castType = $this->casts[$name];
            $exploded = is_string($castType) ? explode(':', $castType, 2) : [$castType];
            $castType = $exploded[0];
            $param = $exploded[1] ?? null;
            switch ($castType) {
                case 'int':
                case 'integer':
                    return (int) $value;
                case 'real':
                case 'float':
                case 'double':
                    return $this->fromFloat($value);
                case 'decimal':
                    return $this->asDecimal($value, explode(':', $this->casts[$name], 2)[1]);
                case 'string':
                    return (string) $value;
                case 'bool':
                case 'boolean':
                    return (bool) $value;
                case 'object':
                case 'array':
                case 'json':
                    if ($value instanceof BaseCollection) {
                        return $value->all();
                    } elseif ($value instanceof Arrayable) {
                        return $value->toArray();
                    }
                    return (array) $value;
                case 'collection':
                    return new BaseCollection($value);
                case 'date':
                    return $this->asDate($value);
                case 'datetime':
                case 'custom_datetime':
                    return $this->asDateTime($value, $param);
                case 'timestamp':
                    return $this->asTimestamp($value);
            }

            if ($this->isEnumCastable($name)) {
                return $this->getEnumCastableAttributeValue($name, $value);
            }

            if (is_string($castType) && class_exists($castType)) {
                return $this->getClassCastableAttributeValue($name, $value);
            }
        }

        return $value;
    }

    /**
     * Cast the given attribute to an enum.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getEnumCastableAttributeValue(string $key, mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $castType = $this->casts[$key];

        if ($value instanceof $castType) {
            return $value;
        }

        return $this->getEnumCaseFromValue($castType, $value);
    }

    /**
     * Determine if the given key is cast using an enum.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEnumCastable(string $key): bool
    {
        if (! array_key_exists($key, $this->casts)) {
            return false;
        }

        if (in_array($this->casts[$key], $this->__primitiveCastTypes)) {
            return false;
        }

        return enum_exists($this->casts[$key]);
    }

    /**
     * Get an enum case instance from a given class and value.
     *
     * @param  string  $enumClass
     * @param  string|int  $value
     * @return \UnitEnum|\BackedEnum
     */
    protected function getEnumCaseFromValue($enumClass, $value): \BackedEnum|\UnitEnum
    {
        return is_subclass_of($enumClass, \BackedEnum::class)
            ? $enumClass::from($value)
            : constant($enumClass.'::'.$value);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string|null  $value
     * @param  bool  $asObject
     * @return mixed
     */
    public function fromJson(?string $value, bool $asObject = false): mixed
    {
        return json_decode($value, ! $asObject);
    }

    /**
     * Decode the given float.
     *
     * @param  mixed  $value
     * @return int|float
     */
    public function fromFloat(mixed $value): int|float
    {
        return match ((string) $value) {
            'Infinity' => INF,
            '-Infinity' => -INF,
            'NaN' => NAN,
            default => (float) $value,
        };
    }

    /**
     * Return a decimal as string.
     *
     * @param  float|string  $value
     * @param  int  $decimals
     * @return string
     */
    protected function asDecimal(float|string $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    /**
     * Return a timestamp as DateTime object with time set to 00:00:00.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDate(mixed $value): Carbon
    {
        return $this->asDateTime($value)->startOfDay();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @param  string|null  $format
     * @return bool|\Illuminate\Support\Carbon|string
     */
    protected function asDateTime(mixed $value, string|null $format = null): bool|Carbon|string
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            $return = Date::instance($value);
            if ($format) {
                return $return->format($format);
            }
            return $return;
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof \DateTimeInterface) {
            $return = Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
            if ($format) {
                return $return->format($format);
            }
            return $return;
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            $return = Date::createFromTimestamp($value);
            if ($format) {
                return $return->format($format);
            }
            return $return;
        }

        $format = $format ?: $this->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (\InvalidArgumentException $e) {
            $date = false;
        }

        $return = $date ?: Date::parse($value);
        if ($format) {
            return $return->format($format);
        }
        return $return;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp(mixed $value): int
    {
        return $this->asDateTime($value)->getTimestamp();
    }

    /**
     * Cast the given attribute using a custom cast class.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getClassCastableAttributeValue(string $key, mixed $value): mixed
    {
        if (isset($this->classCastCache[$key])) {
            return $this->classCastCache[$key];
        } else {
            $caster = $this->resolveCasterClass($key);

            $value = $caster instanceof CastsInboundAttributes
                ? $value
                : $caster->get($this, $key, $value, $this->fields);

            if ($caster instanceof CastsInboundAttributes || ! is_object($value)) {
                unset($this->classCastCache[$key]);
            } else {
                $this->classCastCache[$key] = $value;
            }

            return $value;
        }
    }

    /**
     * Resolve the custom caster class for a given key.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function resolveCasterClass(string $key): mixed
    {
        $castType = $this->casts[$key];

        $arguments = [];

        if (is_string($castType) && str_contains($castType, ':')) {
            $segments = explode(':', $castType, 2);

            $castType = $segments[0];
            $arguments = explode(',', $segments[1]);
        }

        if (is_subclass_of($castType, Castable::class)) {
            $castType = $castType::castUsing($arguments);
        }

        if (is_object($castType)) {
            return $castType;
        }

        return new $castType(...$arguments);
    }
}
