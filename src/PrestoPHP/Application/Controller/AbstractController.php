<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\Application\Controller;

use PrestoPHP\Framework\Application;
use PrestoPHP\Framework\Application\Kernel\ClassResolver\Factory\FactoryResolver;

abstract class AbstractController {
    protected $application;
    protected $factory;

    public function __construct(Application $app = null)
    {
        if($app !== null) $this->setApplication($app);
        $this->init();
    }

    public function init() {}

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param mixed $application
     */
    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }

    protected function assign(array $data = [])
    {
        return $data;
    }

    protected function getFactory() {
        if($this->factory === null) $this->factory = $this->resolveFactory();
        $this->factory->setApplication($this->application);

        return $this->factory;
    }

    private function resolveFactory()
    {
        return $this->getFactoryResolver()->resolve($this);
    }

    private function getFactoryResolver()
    {
        return new FactoryResolver();
    }


}
