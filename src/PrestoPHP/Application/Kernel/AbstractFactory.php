<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework\Application\Kernel;

use PrestoPHP\Framework\Application;

abstract class AbstractFactory
{
    protected Application $application;

    public function setApplication(Application $application)
    {
        $this->application = $application;
    }
}
