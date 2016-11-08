<?php
/**
 * This file is part of EC-CUBE.
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Tests\Web;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class OrderPdfAdminControllerTest extends AbstractAdminWebTestCase
{
    protected $Order;

    public function setUp()
    {
        parent::setUp();
        $this->initOrderData();
    }

    private function initOrderData()
    {
        $Customer = $this->createCustomer();

        $this->Order = $this->createOrder($Customer);
    }

    public function testRoutingOrderIndex()
    {
        $orderId = $this->Order->getId();
        $Status = $this->app['eccube.repository.order_status']->find(5);
        //update order_date to show on search
        $this->app['eccube.repository.order']->changeStatus($orderId, $Status);

        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order'),
            array('admin_search_order' => array(
                '_token' => 'dummy',
                'multi' => '',
            ))
        );

        $expectedText = '帳票出力';
        $actualNode = $crawler->filter('#dropmenu')->html();
        $this->assertContains($expectedText, $actualNode);
    }

    public function testRoutingOrderPdfDownload()
    {
        $orderId = $this->Order->getId();
        $crawler = $this->client->request('GET', $this->app->url('admin_order_pdf').'?ids'.$orderId.'=on'
        );
        $this->assertContains('この内容で作成する', $crawler->html());

        $form = $this->getForm($crawler);
        $this->client->submit($form);

        $this->actual = $this->client->getResponse()->headers->get('Content-Type');
        $this->expected = 'application/pdf';
        $this->verify();
    }

    /**
     * @param Crawler $crawler
     *
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function getForm(Crawler $crawler)
    {
        $form = $crawler->selectButton('この内容で作成する')->form();
        $form['admin_order_pdf[_token]'] = 'dummy';

        return $form;
    }
}
