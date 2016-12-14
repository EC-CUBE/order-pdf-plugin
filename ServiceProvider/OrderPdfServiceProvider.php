<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\ServiceProvider;

use Eccube\Common\Constant;
use Plugin\OrderPdf\Event\OrderPdf;
use Plugin\OrderPdf\Event\OrderPdfLegacy;
use Plugin\OrderPdf\Form\Type\OrderPdfType;
use Plugin\OrderPdf\Service\OrderPdfService;
use Plugin\OrderPdf\Util\Version;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

// include log functions (for 3.0.0 - 3.0.11)
require_once __DIR__.'/../log.php';

/**
 * Class OrderPdfServiceProvider.
 */
class OrderPdfServiceProvider implements ServiceProviderInterface
{
    /**
     * Register service function.
     *
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        // Repository
        $app['orderpdf.repository.order_pdf'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\OrderPdf\Entity\OrderPdf');
        });

        // Order pdf event
        $app['orderpdf.event.order_pdf'] = $app->share(function () use ($app) {
            return new OrderPdf($app);
        });

        // Order pdf legacy event
        $app['orderpdf.event.order_pdf_legacy'] = $app->share(function () use ($app) {
            return new OrderPdfLegacy($app);
        });

        // ============================================================
        // コントローラの登録
        // ============================================================
        // 管理画面定義
        $admin = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
        }
        // 帳票の作成
        $admin->match('/plugin/order-pdf', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::index')
            ->bind('plugin_admin_order_pdf');

        // PDFファイルダウンロード
        $admin->post('/plugin/order-pdf/download', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::download')
            ->bind('plugin_admin_order_pdf_download');

        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        // ============================================================
        // Formの登録
        // ============================================================
        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new OrderPdfType($app);

            return $types;
        }));

        // -----------------------------
        // サービスの登録
        // -----------------------------
        // 帳票作成
        $app['orderpdf.service.order_pdf'] = $app->share(function () use ($app) {
            return new OrderPdfService($app);
        });

        // ============================================================
        // メッセージ登録
        // ============================================================
        $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
        if (file_exists($file)) {
            $app['translator']->addResource('yaml', $file, $app['locale']);
        }

        // initialize logger (for 3.0.0 - 3.0.8)
        if (!Version::isSupportMethod()) {
            eccube_log_init($app);
        }
    }

    /**
     * Boot function.
     *
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }
}
