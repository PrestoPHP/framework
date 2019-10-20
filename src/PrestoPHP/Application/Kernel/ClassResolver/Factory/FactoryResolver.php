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

class FactoryResolver extends AbstractFactoryResolver {
    const CLASS_NAME_PATTERN = '%s\\Code\\%s\\%s\\%sFactory';

    public function getClassNamePattern() {
        return self::CLASS_NAME_PATTERN;
    }

}
