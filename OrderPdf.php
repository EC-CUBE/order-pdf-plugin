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

use Eccube\Event\RenderEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;

class OrderPdf
{

    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 受注マスター表示、検索ボタンクリック時のEvent Fock.
     * 下記の項目を追加する.
     * ・検索結果一覧のコンボボックスに「帳票出力」を追加
     * ・検索結果一覧の上部に「一括帳票出力を追加
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderPdfBefore(FilterResponseEvent $event)
    {
        $app = $this->app;
        $request = $event->getRequest();
        $response = $event->getResponse();
        $id = $request->attributes->get('id');

        $response->setContent($this->getHtml($request, $response, $id));
        $event->setResponse($response);
    }

    /**
     * EC-CUBEの受注マスター画面のHTMLを取得し、帳票関連項目を書き込む
     *
     * @param unknown $request
     * @param unknown $response
     * @param unknown $id
     * @return mixed
     */
    private function getHtml($request, $response, $id) {

        // 検索結果一覧の下部に帳票出力を追加する

        // 受注管理-受注マスターのHTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());

        // [orm id="dropdown-form"]の最終項目に追加(レイアウトに依存（時間無いのでベタ）)
        $html  = $crawler->html();

        $parts = $this->app->renderView(
            'OrderPdf/View/admin/order_pdf_menu.twig'
        );

         try {
            // ※商品編集画面 idなりclassなりがきちんとつかないとDOMをいじるのは難しい
            // また、[その他]メニューの中に入れ込もうとしたがJQUERYのイベントが動作するので不可
            // = = = = = = = = =
            // その他メニューに追加するバージョン
            $form  = $crawler->filter('#dropmenu .dropdown-menu')->last()->html();
            $newForm = $form . $parts;

            $html = str_replace($form, $newForm , $html);
         } catch (\InvalidArgumentException $e) {
            // no-op
        }

        return html_entity_decode($html);
    }

}
