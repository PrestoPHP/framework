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
        if(class_exists($class))
        {
            $controller = new $class;
            $controller->setApplication($this->application);
            switch ($type) {
                case 'get':
                    $this->application->get($path, [$controller, $action]);
                    break;
                case 'post':
                    $this->application->post($path, [$controller, $action]);
                    break;
                case 'put':
                    $this->application->put($path, [$controller, $action]);
                    break;
                case 'delete':
                    $this->application->delete($path, [$controller, $action]);
                    break;
                case 'options':
                    $this->application->options($path, [$controller, $action]);
                    break;
                case 'patch':
                    $this->application->patch($path, [$controller, $action]);
                    break;
            }
        } else throw new \Exception("Controller not found");
    }

    protected function createGetController($path, $class, $action) {
        $this->createController($path, $class, $action, 'get');
    }
    protected function createPostController($path, $class, $action) {
        $this->createController($path, $class, $action, 'post');
    }
    protected function createPutController($path, $class, $action) {
        $this->createController($path, $class, $action, 'put');
    }
    protected function createDeleteController($path, $class, $action) {
        $this->createController($path, $class, $action, 'delete');
    }
    protected function createOptionsController($path, $class, $action) {
        $this->createController($path, $class, $action, 'options');
    }
    protected function createPatchController($path, $class, $action) {
        $this->createController($path, $class, $action, 'patch');
    }

}
