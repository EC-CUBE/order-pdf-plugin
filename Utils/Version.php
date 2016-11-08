<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Utils;

use Eccube\Common\Constant;

/**
 * Class Version.
 * Util to check version.
 */
class Version
{
    /**
     * Check support in version Ec cube.
     *
     * @param string $version
     * @param string $operation
     *
     * @return bool|int|mixed|void
     */
    public static function isSupport($version = '3.0.9', $operation = '>=')
    {
        return version_compare(Constant::VERSION, $version, $operation);
    }
}
