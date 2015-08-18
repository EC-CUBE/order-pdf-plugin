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

namespace Plugin\OrderPdf\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class OrderPdfServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // Setting
        // [システム設定]-[プラグイン一覧]に設定リンクを表示する
//         $app->match('/' . $app["config"]["admin_route"] . '/plugin/order_pdf/config', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::index')->bind('plugin_OrderPdf_config');

        // ============================================================
        // リポジトリの登録
        // ============================================================
        // 不要？
        $app['eccube.plugin.order_pdf.repository.order_pdf_plugin'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\OrderPdf\Entity\OrderPdfPlugin');
        });

        // 注文情報テーブルリポジトリ
        $app['eccube.plugin.order_pdf.repository.order_pdf_order'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\OrderPdf\Entity\OrderPdfOrder');
        });
        // 特定商取引法管理
        $app['eccube.plugin.order_pdf.repository.order_pdf_help'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\OrderPdf\Entity\OrderPdfHelp');
        });

        // ============================================================
        // コントローラの登録
        // ============================================================
        // 帳票の作成
        $app->match('/' . $app["config"]["admin_route"] . '/orderPdf', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_order_pdf');

        // PDFファイルダウンロード
        $app->match('/' . $app["config"]["admin_route"] . '/orderPdf/download', '\\Plugin\\OrderPdf\\Controller\\OrderPdfController::download')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_order_pdf_download');

        // ============================================================
        // Formの登録
        // ============================================================
        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\OrderPdf\Form\Type\OrderPdfType($app);
            return $types;
        }));

        // -----------------------------
        // サービスの登録
        // -----------------------------
        // 帳票作成
        $app['eccube.plugin.order_pdf.service.order_pdf'] = $app->share(function () use ($app) {
            return new \Plugin\OrderPdf\Service\OrderPdfService($app);
        });

        // ============================================================
        // メッセージ登録
        // ============================================================
        $app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

            $file = __DIR__ . '/../Resource/locale/message.' . $app['locale'] . '.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
