<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf;

use Eccube\Application;
use Eccube\Event\TemplateEvent;
use Plugin\OrderPdf\Utils\Version;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class OrderPdf Event.
 */
class OrderPdf
{
    /**
     * @var Application
     */
    private $app;

    private $legacyEvent;

    /**
     * OrderPdf constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->legacyEvent = new OrderPdfLegacy($app);
    }

    /**
     * Event for new hook point.
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderRender(TemplateEvent $event)
    {
        log_info('Event: Order pdf hook into the order search render start.');

        /**
         * @var \Twig_Environment $twig
         */
        $twig = $this->app['twig'];

        $twigAppend = $twig->getLoader()->getSource('OrderPdf/Resource/template/admin/order_pdf_menu.twig');
        /**
         * @var string $twigSource twig template.
         */
        $twigSource = $event->getSource();

        $twigSource = $this->legacyEvent->renderPosition($twigSource, $twigAppend);

        $event->setSource($twigSource);
        log_info('Event: Order pdf hook into the order search render end.');
    }

    /**
     * Event for v3.0.0 - 3.0.8.
     *
     * @param FilterResponseEvent $event
     *
     * @deprecated for since v3.0.0, to be removed in 3.1
     */
    public function onRenderAdminOrderPdfBefore(FilterResponseEvent $event)
    {
        if ($this->supportNewHookPoint()) {
            return;
        }

        $this->legacyEvent->onRenderAdminOrderPdfBefore($event);
    }

    /**
     * Check to support new hookpoint.
     *
     * @return bool v3.0.9以降のフックポイントに対応しているか？
     */
    private function supportNewHookPoint()
    {
        return Version::isSupportGetInstanceFunction();
    }
}
