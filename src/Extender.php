<?php
namespace TelcoLAB\Extender;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Extender
{
    private $classes;

    private $vendor = 'extender';

    public function make($class)
    {
        $classes = is_array($class) ? $class : [$class];

        $this->classes = $this->getValidatorClasses($classes);

        return $this;
    }

    public function extend()
    {
        foreach ($this->classes as $className => $methods) {
            foreach ($methods as $methodName => $validateName) {
                $message = $this->getValidationErrorMessage($validateName);

                $this->pushMethodToValidator($validateName, $className . '@' . $methodName, $message);
            }
        }
    }

    public function getValidatorClasses(array $classes)
    {
        return Collection::make($classes)->reduce(function ($filteredClasses, $class) {
            if (class_exists($class) && $methods = get_class_methods($class)) {
                $filteredMethods = $this->getValidateMethodsName($methods);
                if (!empty($filteredMethods)) {
                    $filteredClasses[$class] = $filteredMethods;
                }
            }
            return $filteredClasses;
        }, []);

    }

    public function getValidateMethodsName(array $methods)
    {
        return Collection::make($methods)->reduce(function ($filteredMethods, $method) {
            if (Str::startsWith($method, 'validate')) {
                $methodName = $this->formatValidateName($method);
                if (!empty($methodName)) {
                    $filteredMethods[$method] = $methodName;
                }
            }
            return $filteredMethods;
        }, []);
    }

    public function formatValidateName($method, $search = 'validate')
    {
        $validateName = str_replace($search, '', $method);
        return Str::snake($validateName);
    }

    public function pushMethodToValidator($validationName, $methodName, $message)
    {
        return Validator::extend($validationName, $methodName, $message);
    }

    public function getValidationErrorMessage($validationName)
    {
        return Lang::get($this->vendor . '::validation.' . $validationName);
    }
}
