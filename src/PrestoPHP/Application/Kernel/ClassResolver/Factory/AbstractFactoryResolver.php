<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PrestoPHP\Framework\Application\Kernel\ClassResolver\Factory;

use PrestoPHP\Framework\Application\Kernel\ClassResolver\AbstractClassResolver;

abstract class AbstractFactoryResolver extends AbstractClassResolver {

    public function resolve($callerClass)
    {
        $this->setCallerClass($callerClass);

        if($this->isResolvable()) {
            return $this->getResolvedInstance();
        }

        throw new \Exception("Factory not found Exception");
    }

    protected function generateClassName($namespace)
    {
        $mapper = [
            '%namespace%' => $namespace,
            '%bundle%' => $this->getBundle(),
            '%factory%' => $this->getBundle()
        ];

        $className = str_replace(
            array_keys($mapper),
            array_values($mapper),
            $this->getClassPattern()
        );

        return $className;
    }

    protected function getClassPattern()
    {
        return sprintf(
            $this->getClassNamePattern(),
            APPLICATION_NAME,
            '%namespace%',
            '%bundle%',
            '%factory%'
        );

    }
}
