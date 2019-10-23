<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\Twig\Plugin\Provider;

use Pimple\Container;
use PrestoPHP\Api\BootableProviderInterface;
use PrestoPHP\Application;
use PrestoPHP\Provider\TwigServiceProvider as PrestoPHPTwigServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TwigServiceProvider extends PrestoPHPTwigServiceProvider implements BootableProviderInterface {
    protected $app;

    public function __construct() 	{

    }

    public function register(Container $app) {
        $this->app = $app;
        parent::register($app);
    }

    public function onKernelView(GetResponseForControllerResultEvent $event) {
        $response = $event->getControllerResult();

        if(is_array($response) || empty($response)) {
            $response = $this->render((array) $response);

            if($response instanceof Response) $event->setResponse($response);
        }

    }

    public function boot(Application $app) {
        $app['dispatcher']->addListener(KernelEvents::VIEW, [$this, 'onKernelView'], 0);
    }

    protected function render(array $params = []) {
        $route = $this->getRouteFromRequest();

        if($route === null) return null;

        return $this->app->render($route .'.twig', $params);
    }

    protected function getRouteFromRequest() {
        $request = $this->app['request_stack']->getCurrentRequest();
        $controller = $request->attributes->get("_controller");

        if(empty($controller)) return null;

        if(strpos($controller, ':') !== false) {
            list($service, $action) = explode(':', $controller);
            $controllerNameSpace = get_class($this->app[$service]);
        } else if(strpos($controller, '::') !== false) {
            list($controllerNameSpace, $action) = explode('::', $controller);
        } else {
            throw new \LogicException("Cannot parse Route from Request");
        }

        list($application, $namespace, $module, $bundle, $layer, $controllerName) = explode('\\', $controllerNameSpace);

        $controller = $this->filter(str_replace('Controller', '', $controllerName));
        $action = $this->filter(str_replace('Action', '', $action));

        return $bundle.'/'.$controller.'/'.$action;
    }

    protected function filter($string, $separator = '-') {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . addcslashes($separator, '$') . '$2', $string));
    }


}
