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

use Plugin\OrderPdf\Form\Type\OrderPdfType;
use Plugin\OrderPdf\Service\OrderPdfService;
use Plugin\OrderPdf\Utils\Version;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Yaml;

// include log functions (for 3.0.0 - 3.0.11)
require_once(__DIR__.'/../log.php');

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
        $app['eccube.plugin.order_pdf.repository.order_pdf'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\OrderPdf\Entity\OrderPdf');
        });

        // ============================================================
        // コントローラの登録
        // ============================================================
        // 帳票の作成
        $app->match('/'.$app['config']['admin_route'].'/order-pdf', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::index')
            ->bind('admin_order_pdf');

        // PDFファイルダウンロード
        $app->match('/'.$app['config']['admin_route'].'/order-pdf/download', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::download')
            ->bind('admin_order_pdf_download');

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
        $app['eccube.plugin.order_pdf.service.order_pdf'] = $app->share(function () use ($app) {
            return new OrderPdfService($app);
        });

        // ============================================================
        // メッセージ登録
        // ============================================================
        $app['translator'] = $app->share($app->extend('translator', function (Translator $translator, BaseApplication $app) {
            $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));

        // Config
        $app['config'] = $app->share($app->extend('config', function ($config) {
            // Update constants
            $constantFile = __DIR__.'/../Resource/config/constant.yml';
            if (file_exists($constantFile)) {
                $constant = Yaml::parse(file_get_contents($constantFile));
                if (!empty($constant)) {
                    // Replace constants
                    $config = array_replace_recursive($config, $constant);
                }
            }

            return $config;
        }));

        // initialize logger (for 3.0.0 - 3.0.8)
        if (!Version::isSupportGetInstanceFunction()) {
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
