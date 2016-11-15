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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class OrderPdfLegacy.
 * For old event.
 *
 * @deprecated support since 3.0.0, it will remove on 3.1
 */
class OrderPdfLegacy
{
    /**
     * @var Application
     */
    private $app;

    /**
     * OrderPdfLegacy constructor.
     *
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 受注マスター表示、検索ボタンクリック時のEvent Fork.
     * 下記の項目を追加する.
     * ・検索結果一覧のコンボボックスに「帳票出力」を追加
     * ・検索結果一覧の上部に「一括帳票出力を追加.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderPdfBefore(FilterResponseEvent $event)
    {
        log_info('EventLegacy: The Order pdf hook into the order search start');

        if (!$this->app->isGranted('ROLE_ADMIN')) {
            log_info('EventLegacy: You need permission manager to be able to use this function.');

            return;
        }

        $response = $event->getResponse();

        $response->setContent($this->getHtml($response));
        $event->setResponse($response);

        log_info('EventLegacy: The Order pdf hook into the order search end');
    }

    /**
     * Render position.
     *
     * @param string $html
     * @param string $part
     *
     * @return string
     */
    public function renderPosition($html, $part)
    {
        // For old and new ec-cube version
        // Search group
        // Group 1
        $search = '/(<li\s+id="dropmenu"[\s\S]*)'; // Points to start the search.
        // Group 2
        $search .= '(<ul\s+class="dropdown\-menu"[\s\S]*)'; // start drop down section.
        // Group 3
        $search .= '(<\/li>[\n\s]*<\/ul>)'; // The end of the dropdown section.
        // Group 4
        $search .= '([\s\S]*<form\s+id="dropdown\-form")/'; // Points to end the search.

        $arrMatch = array();
        preg_match($search, $html, $arrMatch, PREG_OFFSET_CAPTURE);

        if (!isset($arrMatch[4])) {
            return $html;
        }
        $oldHtml = $arrMatch[2][0];

        // first html
        $oldHtmlStartPos = $arrMatch[2][1];
        $firstHalfHtml = substr($html, 0, $oldHtmlStartPos);

        // end html
        $oldHtmlEndPos = $arrMatch[3][1];
        $endHalfHtml = substr($html, $oldHtmlEndPos);

        // new html
        $newHtml = str_replace('</ul>', $part.'</ul>', $oldHtml);

        $html = $firstHalfHtml.$newHtml.$endHalfHtml;

        return $html;
    }

    /**
     * EC-CUBEの受注マスター画面のHTMLを取得し、帳票関連項目を書き込む
     *
     * @param Response $response
     *
     * @return mixed
     */
    private function getHtml(Response $response)
    {

        // 検索結果一覧の下部に帳票出力を追加する

        // 受注管理-受注マスターのHTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());

        // [Form id="dropdown-form"]の最終項目に追加(レイアウトに依存（時間無いのでベタ）)
        $html = $this->getHtmlFromCrawler($crawler);

        $parts = $this->app->renderView(
            'OrderPdf/Resource/template/admin/order_pdf_menu.twig'
        );

        try {
            // ※商品編集画面 idなりclassなりがきちんとつかないとDOMをいじるのは難しい
            // また、[その他]メニューの中に入れ込もうとしたがJQUERYのイベントが動作するので不可
            // = = = = = = = = =
            // その他メニューに追加するバージョン
            $form = $crawler->filter('#dropmenu .dropdown-menu')->last()->html();
            $newForm = $form.$parts;

            $html = str_replace($form, $newForm, $html);
        } catch (\InvalidArgumentException $e) {
            // no-op
        }

        return html_entity_decode($html);
    }

    /**
     * 解析用HTMLを取得.
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function getHtmlFromCrawler(Crawler $crawler)
    {
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }

        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
}
