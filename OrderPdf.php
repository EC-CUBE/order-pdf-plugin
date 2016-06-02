<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\OrderPdf;

use Eccube\Common\Constant;
use Eccube\Event\RenderEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class OrderPdf
{

    private $app;
    private $legacyEvent;

    public function __construct($app)
    {
        $this->app = $app;
        $this->legacyEvent = new OrderPdfLegacy($app);
    }

    public function onResponseAdminOrderPdfBefore(FilterResponseEvent $event)
    {
        $this->legacyEvent->onRenderAdminOrderPdfBefore($event);
    }

    /**
     * for v3.0.0 - 3.0.8
     * @deprecated for since v3.0.0, to be removed in 3.1.
     */
    public function onRenderAdminOrderPdfBefore(FilterResponseEvent $event)
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onRenderAdminOrderPdfBefore($event);
    }

    /**
     * @return bool v3.0.9以降のフックポイントに対応しているか？
     */
    private function supportNewHookPoint()
    {
        return version_compare('3.0.9', Constant::VERSION, '<=');
    }

}
