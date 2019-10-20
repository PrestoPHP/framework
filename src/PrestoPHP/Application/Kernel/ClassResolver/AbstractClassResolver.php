<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PrestoPHP\Framework\Application\Kernel\ClassResolver;

use Symfony\Component\Finder\Finder;

abstract class AbstractClassResolver {
    protected $resolvedClassName;
    protected $callerClassName;
    protected $callerClassParts;

    const KEY_BUNDLE = 3;

    /**
     * @return mixed
     */
    public function getResolvedClassName()
    {
        return $this->resolvedClassName;
    }

    protected function getResolvedInstance()
    {
        return new $this->resolvedClassName();
    }

    public function isResolvable() {
        $classNames = $this->generateClassNames();

        foreach ($classNames as $className)
        {
            if(class_exists($className))
            {
                $this->resolvedClassName = $className;

                return true;
            }
        }

        return false;
    }

    public function getBundle()
    {
        return $this->callerClassParts[self::KEY_BUNDLE];
    }

    abstract protected function generateClassName($namespace);

    abstract protected function getClassNamePattern();

    protected function setCallerClass($callerClass)
    {
        if (is_object($callerClass))
        {
            $callerClass = get_class($callerClass);
        }

        $this->callerClassName = $callerClass;

        $callerClassParts = [
            self::KEY_BUNDLE => $callerClass
        ];

        if($this->isFQClassName($callerClass))
        {
            $callerClassParts = explode('\\', $callerClass);
        }

        $this->callerClassParts = $callerClassParts;
    }

    protected function isFQClassName($className)
    {
        return (strpos($className, "\\") !== false);
    }

    protected function generateClassNames() {
        $result = [];
        foreach ($this->getNamespaces() as $namespace) {
            $result[] = $this->generateClassName($namespace);
        }

        return $result;
    }

    protected function getNamespaces() {
        defined('SRC_DIR') || define('SRC_DIR', '/src');
        $namespaces = [];
        $finder = new Finder();
        $finder->depth('== 0');
        $result = $finder->directories()->in(APPLICATION_DIR.SRC_DIR.'/Code');
        foreach ($result as $res) {
            $namespaces[] = $res->getRelativePathname();
        }

        return $namespaces;
    }
}
