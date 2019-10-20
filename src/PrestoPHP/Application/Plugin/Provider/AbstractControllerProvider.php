<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\Application\Plugin\Provider;

use PrestoPHP\Api\ControllerProviderInterface;
use PrestoPHP\Application;
use PrestoPHP\ControllerCollection;

abstract class AbstractControllerProvider implements ControllerProviderInterface {
    /**
     * @var \PrestoPHP\Controller\Collection
     */
    protected $controllerCollection;
    /**
     * @var \PrestoPHP\Application
     */
    protected $application;

    /**
     * @var bool
     */
    protected $forceHttps;

    public function __construct($forceHttps = false)
    {
        $this->forceHttps = $forceHttps;
    }

    abstract protected function setupControllers(Application $app);
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $this->controllerCollection = $app['controllers_factory'];
        $this->application = $app;
        $this->setupControllers($app);

        return $this->controllerCollection;
    }

    protected function createController($path, $class, $action, $type) {
        if(class_exists($class)) {
            $controller = new $class;
            $controller->setApplication($this->application);
            switch ($type) {
                case 'get':
                    $this->application->get('/', [$controller, $action]);
                    break;
            }
        } else throw new \Exception("Controller not found");
    }

    protected function createGetController($path, $class, $action) {
        $this->createController($path, $class, $action, 'get');
    }

}
