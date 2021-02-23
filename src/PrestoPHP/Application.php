<?php
/*
 * This file is part of the PrestoPHP framework.
 *
 * (c) Gunnar Beushausen <gunnar@prestophp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PrestoPHP\Framework;

use PrestoPHP\Application as BaseApplication;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class Application extends BaseApplication
{
    const VERSION = '0.0.2';

    public static function getVersion()
    {
        return self::VERSION;
    }

    /**
     * @param  $type
     * @param  null  $data
     * @param  array $options
     * @return FormBuilderInterface
     */
    public function buildForm($type = FormType::class, $data = null, $options = []): FormBuilderInterface
    {
        return $this['form.factory']->createBuilder($type, $data, $options);
    }
}
