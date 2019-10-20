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

    public function assign(array $data = []) {
        return $data;
    }



}
