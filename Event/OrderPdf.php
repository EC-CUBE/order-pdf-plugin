<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Event;

use Eccube\Event\TemplateEvent;

/**
 * Class OrderPdf Event.
 */
class OrderPdf extends CommonEvent
{
    /**
     * Event for new hook point.
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexRender(TemplateEvent $event)
    {
        log_info('Event: Order pdf hook into the order search render start.');

        /**
         * @var \Twig_Environment $twig
         */
        $twig = $this->app['twig'];

        $twigAppend = $twig->getLoader()->getSource('OrderPdf/Resource/template/admin/order_pdf_menu.twig');
        /**
         * @var string twig template
         */
        $twigSource = $event->getSource();

        $twigSource = $this->renderPosition($twigSource, $twigAppend);

        $event->setSource($twigSource);
        log_info('Event: Order pdf hook into the order search render end.');
    }
}
