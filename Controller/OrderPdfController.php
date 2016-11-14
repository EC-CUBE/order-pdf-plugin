<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Plugin\OrderPdf\Repository\OrderPdfRepository;
use Plugin\OrderPdf\Service\OrderPdfService;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class OrderPdfController.
 */
class OrderPdfController extends AbstractController
{
    /**
     * 納品書の設定画面表示.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws NotFoundHttpException
     */
    public function index(Application $app, Request $request)
    {
        /* @var Form $form */
        $form = $app['form.factory']->createBuilder('admin_order_pdf')->getForm();

        // requestから受注番号IDの一覧を取得する
        $ids = $this->getIds($request);

        if (count($ids) == 0) {
            $app->addError('admin.order_pdf.parameter.notfound', 'admin');

            return $app->redirect($app->url('admin_order'));
        }

        // Formへの設定
        $form->get('ids')->setData(implode(',', $ids));

        return $app->render('OrderPdf/Resource/template/admin/order_pdf.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * 作成ボタンクリック時の処理
     * 帳票のPDFをダウンロードする.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     *
     * @throws BadRequestHttpException
     */
    public function download(Application $app, Request $request)
    {
        /* @var Form $form */
        $form = $app['form.factory']->createBuilder('admin_order_pdf')->getForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            throw new BadRequestHttpException();
        }

        // Validation
        if (!$form->isValid()) {
            return $app->render('OrderPdf/Resource/template/admin/order_pdf.twig', array(
                'form' => $form->createView(),
            ));
        }

        // サービスの取得
        /* @var OrderPdfService $service */
        $service = $app['eccube.plugin.order_pdf.service.order_pdf'];

        $arrData = $form->getData();

        // 購入情報からPDFを作成する
        $status = $service->makePdf($arrData);

        // 異常終了した場合の処理
        if (!$status) {
            $app->addError('admin.order_pdf.download.failure', 'admin');

            return $app->render('OrderPdf/Resource/template/admin/order_pdf.twig', array(
                'form' => $form->createView(),
            ));
        }

        // ダウンロードする
        $response = new Response(
            $service->outputPdf(),
            200,
            array('content-type' => 'application/pdf')
        );

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名をreceipt.pdfに指定
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$service->getPdfFileName().'"');

        // Save input to DB
        $arrData['admin'] = $app->user();
        /* @var OrderPdfRepository $repos */
        $repos = $app['eccube.plugin.order_pdf.repository.order_pdf'];

        $repos->save($arrData);

        return $response;
    }

    /**
     * requestから注文番号のID一覧を取得する.
     *
     * @param Request $request
     *
     * @return array $isList
     */
    protected function getIds(Request $request)
    {
        $isList = array();

        // -----------------------------
        // POSTリクエストの場合の処理
        // ボタンのバージョン
        // foreach ($request->request->all() as $key => $val) {
        // // キーが一致
        // if(preg_match('/^ids\d+$/', $key)) {
        // if (!empty($val) && $val == 'on') {
        // $isList[] = intval(str_replace("ids", "", $key));
        // }
        // }
        // -----------------------------

        // その他メニューのバージョン
        $queryString = $request->getQueryString();

        if (empty($queryString)) {
            return $isList;
        }

        // クエリーをparseする
        // idsX以外はない想定
        parse_str($queryString, $ary);

        foreach ($ary as $key => $val) {
            // キーが一致
            if (preg_match('/^ids\d+$/', $key)) {
                if (!empty($val) && $val == 'on') {
                    $isList[] = intval(str_replace('ids', '', $key));
                }
            }
        }

        // id順にソートする
        sort($isList);

        return $isList;
    }
}
