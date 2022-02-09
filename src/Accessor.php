<?php

namespace Bfg\Resource;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Accessor
{
    /**
     * @var array|object|string
     */
    private mixed $subject;

    /**
     * Accessor constructor.
     *
     * @param  object|array|string  $subject
     */
    public function __construct(object|array|string $subject)
    {
        if (is_array($subject) || is_object($subject)) {
            $this->subject = $subject;
        } elseif (is_string($subject)) {
            $this->subject = new $subject;
        }
    }

    /**
     * @param  object|array|string  $subject
     * @return Accessor
     */
    public static function create(object|array|string $subject): self
    {
        return new static($subject);
    }

    /**
     * [ ==|=|is  (VALUE)] = where('name', '=', 'value')
     * [ <=       (VALUE)] = where('name', '<=', 'value')
     * [ >=       (VALUE)] = where('name', '>=', 'value')
     * [ <        (VALUE)] = where('name', '<', 'value')
     * [ >        (VALUE)] = where('name', '>', 'value')
     * [ !=|not   (VALUE)] = where('name', '!=', 'value')
     * [ %%|like  (VALUE)] = where('name', 'like', '%value%')
     * [ %|%like  (VALUE)] = where('name', 'like', '%value')
     * [ !%|like% (VALUE)] = where('name', 'like', 'value%')
     * [ in       (VALUE)] = whereIn('name', explode(';', 'value;value...'))
     * [ not in   (VALUE)] = whereNotIn('name', explode(';', 'value;value...'))
     * [ not null (VALUE)] = whereNotNull('name')
     * [ null     (VALUE)] = whereNull('name').
     *
     * @param array $instructions
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Relation
     */
    public function eloquentInstruction(array $instructions): mixed
    {
        if (
            $this->subject instanceof Builder ||
            $this->subject instanceof Relation ||
            $this->subject instanceof Model
        ) {
            foreach ($instructions as $name => $instruction) {
                if ($instruction instanceof \Closure) {
                    $result = call_user_func($instruction, $this->subject);

                    if ($result) {
                        $this->subject = $result;
                    }
                } elseif (preg_match('/^\s*([\=\=]{2}|[\=]|[\<\=]{2}|[\>\=]{2}|[\<]|[\>]|[\!\=]{2}|[\!\%]{2}|[\%]like|like[\%]|[\%\%]{2}|[\%]|in|not\sin|not\snull|not|null|like|is|)\s*(.*)/', $instruction, $match)) {
                    $option = $match[1];

                    $value = $match[2];

                    if (empty($option)) {
                        $option = '=';
                    }
                    if ($option == '==') {
                        $option = '=';
                    }
                    if ($option == 'is') {
                        $option = '=';
                    }
                    if ($option == 'not') {
                        $option = '!=';
                    }
                    if ($option == 'like') {
                        $option = '%%';
                    }
                    if ($option == 'like%') {
                        $option = '!%';
                    }
                    if ($option == '%like') {
                        $option = '%';
                    }

                    if ($option == '=' || $option == '<=' || $option == '>=' || $option == '<' || $option == '>' || $option == '!=') {
                        $this->subject = $this->subject->where($name, $option, $value);
                    } elseif ($option == '%%') {
                        $this->subject = $this->subject->where($name, 'like', "%{$value}%");
                    } elseif ($option == '%') {
                        $this->subject = $this->subject->where($name, 'like', "%{$value}");
                    } elseif ($option == '!%') {
                        $this->subject = $this->subject->where($name, 'like', "{$value}%");
                    } elseif ($option == 'in') {
                        $this->subject = $this->subject->whereIn($name, explode(';', $value));
                    } elseif ($option == 'not in') {
                        $this->subject = $this->subject->whereNotIn($name, explode(';', $value));
                    } elseif ($option == 'not null') {
                        $this->subject = $this->subject->whereNotNull($name);
                    } elseif ($option == 'not null') {
                        $this->subject = $this->subject->whereNull($name);
                    }
                }
            }
        }

        return $this->subject;
    }

    /**
     * @param  string  $path
     * @param  bool  $locale
     * @return mixed
     */
    public function dotCall(string $path, bool $locale = false): mixed
    {
        $split = explode('.', $path);

        foreach ($split as $item) {
            try {
                if ($this->subject instanceof \Illuminate\Support\Collection) {
                    $this->subject = $this->subject->get($item);
                } elseif (is_object($this->subject)) {
                    if (
                        ! $locale &&
                        $this->subject instanceof Model &&
                        method_exists($this->subject, 'getTranslations') &&
                        method_exists($this->subject, 'isTranslatableAttribute')
                    ) {
                        if ($this->subject->isTranslatableAttribute($item)) {
                            $this->subject = $this->subject->getTranslations($item);
                        } else {
                            try {
                                $this->subject = $this->subject->{$item};
                            } catch (Exception $exception) {
                                $this->subject = $this->subject->{$item}();
                            }
                        }
                    } else {
                        try {
                            $this->subject = $this->subject->{$item};
                        } catch (Exception $exception) {
                            $this->subject = $this->subject->{$item}();
                        }
                    }
                } elseif (is_array($this->subject)) {
                    $this->subject = $this->subject[$item] ?? null;
                }

                if ($this->subject === null) {
                    return null;
                }
            } catch (Exception $exception) {
                return null;
            }
        }

        return $this->subject;
    }
}
