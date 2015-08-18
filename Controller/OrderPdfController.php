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

namespace Plugin\OrderPdf\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderPdfController
{

    private $main_title;

    private $sub_title;

    public function __construct()
    {}

    /**
     * 納品書の設定画面表示.
     * @param Application $app
     * @param Request $request
     * @param unknown $id
     * @throws NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        // リクエストGETで良ければ削除
        // // POSTでない場合は終了する
        // if ('POST' !== $request->getMethod()) {
        // throw new BadRequestHttpException();
        // }
        $form = $app['form.factory']->createBuilder('admin_order_pdf')->getForm();

        // requestから受注番号IDの一覧を取得する
        $ids = $this->getIds($request);

        if (count($ids) == 0) {
            $app->addError('admin.orecer_pdf.parameter.notfound', 'admin');
            return $app->redirect($app->url('admin_order'));
        }

        // Formへの設定
        $form->get('ids')->setData(implode(',', $ids));

        return $app->render('OrderPdf/View/admin/order_pdf.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * 作成ボタンクリック時の処理
     * 帳票のPDFをダウンロードする
     *
     * @param Application $app
     * @param Request $request
     * @throws BadRequestHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(Application $app, Request $request)
    {

        // POSTでない場合は終了する
        if ('POST' !== $request->getMethod()) {
            throw new BadRequestHttpException();
        }
        $form = $app['form.factory']->createBuilder('admin_order_pdf')->getForm();
        $form->handleRequest($request);

        // validation
        if(!$form->isValid()){
            return $app->render('OrderPdf/View/admin/order_pdf.twig', array(
                'form' => $form->createView()
            ));
        }

        // サービスの取得
        $service = $app['eccube.plugin.order_pdf.service.order_pdf'];

        // 購入情報からPDFを作成する
        $status = $service->makePdf($form->getData());

        // 異常終了した場合の処理
        if (!$status) {
            $service->close();
            $app->addError('admin.order_pdf.download.failure', 'admin');
            return $app->render('OrderPdf/View/admin/order_pdf.twig', array(
                'form' => $form->createView()
            ));
        }

        // ダウンロードする
        $response = new Response(
            $service->outputPdf(),
            200,
            array('content-type' => 'application/pdf')
        );

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名をreceipt.pdfに指定
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $service->getPdfFileName() .'"');

        return $response;


    }

    /**
     * requestから注文番号のID一覧を取得する
     *
     * @param unknown $request
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
                if (! empty($val) && $val == 'on') {
                    $isList[] = intval(str_replace("ids", "", $key));
                }
            }
        }

        // id順にソートする
        sort($isList);
        return $isList;
    }
}
