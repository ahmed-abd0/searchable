<?php

namespace Abdo\Searchable\AttributeHandler;

use Closure;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

class AttributeHandler
{

    private $reflected;

    private $object;

    public function __construct(Object $obj)
    {
        $this->object = $obj;
        $this->reflected = new ReflectionObject($obj);
    }

    public static function filter(array $reflections, string $attribute, ?array $arguments = null): Collection
    {
        return static::filterByAttribute($reflections, $attribute)
            ->when($arguments, static::filterByArguments($attribute, $arguments));
    }

    public function findMethods(string $attribute, ?array $arguments = null): Collection
    {
        return static::filter($this->reflected->getMethods(), ...func_get_args());
    }

    public function findProperties(string $attribute, ?array $arguments = null): Collection
    {
        return static::filter($this->reflected->getProperties(), ...func_get_args());
    }

    public function findMethod(string $attribute, ?array $arguments = null): ?ReflectionMethod
    {
        return $this->findMethods(...func_get_args())->first();
    }

    public function findProperty(string $attribute, ?array $arguments = null): ?ReflectionProperty
    {
        return $this->findProperties(...func_get_args())->first();
    }

    public function findPropertyName(string $attribute, ?array $arguments = null): ?string
    {
        return $this->findProperty(...func_get_args())?->getName();
    }

    public function findPropertyValue(string $attribute, ?array $arguments = null)
    {
        return $this->findProperty(...func_get_args())?->getValue($this->object);
    }

    private static function filterByAttribute(array $reflections, string $attribute): Collection
    {
        return collect($reflections)->filter(
            fn ($reflected) => !empty($reflected->getAttributes($attribute))
        );
    }

    private static function filterByArguments(string $attribute, ?array $arguments = null): Closure
    {
        return function ($reflections) use ($attribute, $arguments) {

            return $reflections->filter(
                fn ($reflected) => collect($reflected->getAttributes($attribute))
                    ->contains(fn ($attribute) => $attribute->getArguments() === $arguments)
            );
        };
    }
}
