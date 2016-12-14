<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderPdf\Service;

use Eccube\Application;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Help;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;

/**
 * Class OrderPdfService.
 * Do export pdf function.
 */
class OrderPdfService extends AbstractFPDIService
{
    // ====================================
    // 定数宣言
    // ====================================
    /** OrderPdf用リポジトリ名 */
    const REPOSITORY_ORDER_PDF = 'eccube.repository.order';

    /** 通貨単位 */
    const MONETARY_UNIT = '円';

    /** ダウンロードするPDFファイルのデフォルト名 */
    const DEFAULT_PDF_FILE_NAME = 'nouhinsyo.pdf';

    /** FONT ゴシック */
    const FONT_GOTHIC = 'kozgopromedium';
    /** FONT 明朝 */
    const FONT_SJIS = 'kozminproregular';

    // ====================================
    // 変数宣言
    // ====================================
    /** @var Application */
    public $app;

    /** @var BaseInfo */
    public $BaseInfo;

    /** 購入詳細情報 ラベル配列
     * @var array
     */
    private $labelCell = array();

    /*** 購入詳細情報 幅サイズ配列
     * @var array
     */
    private $widthCell = array();

    /** 最後に処理した注文番号 @var string */
    private $lastOrderId = null;

    // --------------------------------------
    // Font情報のバックアップデータ
    /** @var string フォント名 */
    private $bakFontFamily;
    /** @var string フォントスタイル */
    private $bakFontStyle;
    /** @var string フォントサイズ */
    private $bakFontSize;
    // --------------------------------------

    // lfTextのoffset
    private $baseOffsetX = 0;
    private $baseOffsetY = -4;

    /** ダウンロードファイル名 @var string */
    private $downloadFileName = null;

    /** 発行日 @var string */
    private $issueDate = '';

    /**
     * コンストラクタ.
     *
     * @param object $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
        parent::__construct();

        // 購入詳細情報の設定を行う
        // 動的に入れ替えることはない
        $this->labelCell[] = '商品名 / 商品コード / [ 規格 ]';
        $this->labelCell[] = '数量';
        $this->labelCell[] = '単価';
        $this->labelCell[] = '金額(税込)';
        $this->widthCell = array(110.3, 12, 21.7, 24.5);

        // Fontの設定しておかないと文字化けを起こす
         $this->SetFont(self::FONT_SJIS);

        // PDFの余白(上左右)を設定
        $this->SetMargins(15, 20);

        // ヘッダーの出力を無効化
        $this->setPrintHeader(false);

        // フッターの出力を無効化
        $this->setPrintFooter(true);
        $this->setFooterMargin();
        $this->setFooterFont(array(self::FONT_SJIS, '', 8));
    }

    /**
     * 注文情報からPDFファイルを作成する.
     *
     * @param array $formData
     *                        [KEY]
     *                        ids: 注文番号
     *                        issue_date: 発行日
     *                        title: タイトル
     *                        message1: メッセージ1行目
     *                        message2: メッセージ2行目
     *                        message3: メッセージ3行目
     *                        note1: 備考1行目
     *                        note2: 備考2行目
     *                        note3: 備考3行目
     *
     * @return bool
     */
    public function makePdf(array $formData)
    {
        // 発行日の設定
        $this->issueDate = '作成日: '.$formData['issue_date']->format('Y年m月d日');
        // ダウンロードファイル名の初期化
        $this->downloadFileName = null;

        // データが空であれば終了
        if (!$formData['ids']) {
            return false;
        }

        // 注文番号をStringからarrayに変換
        $ids = explode(',', $formData['ids']);

        // 空文字列の場合のデフォルトメッセージを設定する
        $this->setDefaultData($formData);

        // テンプレートファイルを読み込む(app配下のテンプレートファイルを優先して読み込む)
        $pdfFile = $this->app['config']['OrderPdf']['const']['pdf_file'];
        $originalPath = __DIR__.'/../Resource/template/'.$pdfFile;
        $userPath = $this->app['config']['template_realdir'].'/../admin/OrderPdf/'.$pdfFile;
        $templateFilePath = file_exists($userPath) ? $userPath : $originalPath;
        $this->setSourceFile($templateFilePath);

        foreach ($ids as $id) {
            $this->lastOrderId = $id;

            // 注文番号から受注情報を取得する
            $order = $this->app[self::REPOSITORY_ORDER_PDF]->find($id);
            if (!$order) {
                // 注文情報の取得ができなかった場合
                continue;
            }

            // PDFにページを追加する
            $this->addPdfPage();

            // タイトルを描画する
            $this->renderTitle($formData['title']);

            // 店舗情報を描画する
            $this->renderShopData();

            // 注文情報を描画する
            $this->renderOrderData($order);

            // メッセージを描画する
            $this->renderMessageData($formData);

            // 受注詳細情報を描画する
            $this->renderOrderDetailData($order);

            // 備考を描画する
            $this->renderEtcData($formData);
        }

        return true;
    }

    /**
     * PDFファイルを出力する.
     *
     * @return string|mixed
     */
    public function outputPdf()
    {
        return $this->Output($this->getPdfFileName(), 'S');
    }

    /**
     * PDFファイル名を取得する
     * PDFが1枚の時は注文番号をファイル名につける.
     *
     * @return string ファイル名
     */
    public function getPdfFileName()
    {
        if (!is_null($this->downloadFileName)) {
            return $this->downloadFileName;
        }
        $this->downloadFileName = self::DEFAULT_PDF_FILE_NAME;
        if ($this->PageNo() == 1) {
            $this->downloadFileName = 'nouhinsyo-No'.$this->lastOrderId.'.pdf';
        }

        return $this->downloadFileName;
    }

    /**
     * フッターに発行日を出力する.
     */
    public function Footer()
    {
        $this->Cell(0, 0, $this->issueDate, 0, 0, 'R');
    }

    /**
     * 作成するPDFのテンプレートファイルを指定する.
     */
    protected function addPdfPage()
    {
        // ページを追加
        $this->AddPage();

        // テンプレートに使うテンプレートファイルのページ番号を取得
        $tplIdx = $this->importPage(1);

        // テンプレートに使うテンプレートファイルのページ番号を指定
        $this->useTemplate($tplIdx, null, null, null, null, true);
    }

    /**
     * PDFに店舗情報を設定する
     * ショップ名、ロゴ画像以外はdtb_helpに登録されたデータを使用する.
     */
    protected function renderShopData()
    {
        // 基準座標を設定する
        $this->setBasePosition();

        // 特定商取引法を取得する
        /* @var Help $Help */
        $Help = $this->app['eccube.repository.help']->get();

        // ショップ名
        $this->lfText(125, 60, $this->BaseInfo->getShopName(), 8, 'B');
        // URL
        $this->lfText(125, 63, $Help->getLawUrl(), 8);
        // 会社名
        $this->lfText(125, 68, $Help->getLawCompany(), 8);
        // 郵便番号
        $text = '〒 '.$Help->getLawZip01().' - '.$Help->getLawZip02();
        $this->lfText(125, 71, $text, 8);
        // 都道府県+所在地
        $lawPref = $Help->getLawPref() ? $Help->getLawPref()->getName() : null;
        $text = $lawPref.$Help->getLawAddr01();
        $this->lfText(125, 74, $text, 8);
        $this->lfText(125, 77, $Help->getLawAddr02(), 8);

        // 電話番号
        $text = 'TEL: '.$Help->getLawTel01().'-'.$Help->getLawTel02().'-'.$Help->getLawTel03();

        //FAX番号が存在する場合、表示する
        if (strlen($Help->getLawFax01()) > 0) {
            $text .= '　FAX: '.$Help->getLawFax01().'-'.$Help->getLawFax02().'-'.$Help->getLawFax03();
        }
        $this->lfText(125, 80, $text, 8);  //TEL・FAX

        // メールアドレス
        if (strlen($Help->getLawEmail()) > 0) {
            $text = 'Email: '.$Help->getLawEmail();
            $this->lfText(125, 83, $text, 8);      // Email
        }

        // ロゴ画像(app配下のロゴ画像を優先して読み込む)
        $logoFile = $this->app['config']['OrderPdf']['const']['logo_file'];
        $originalPath = __DIR__.'/../Resource/template/'.$logoFile;
        $userPath = $this->app['config']['template_realdir'].'/../admin/OrderPdf/'.$logoFile;
        $logoFilePath = file_exists($userPath) ? $userPath : $originalPath;
        $this->Image($logoFilePath, 124, 46, 40);
    }

    /**
     * メッセージを設定する.
     *
     * @param array $formData
     */
    protected function renderMessageData(array $formData)
    {
        $this->lfText(27, 70, $formData['message1'], 8);  //メッセージ1
        $this->lfText(27, 74, $formData['message2'], 8);  //メッセージ2
        $this->lfText(27, 78, $formData['message3'], 8);  //メッセージ3
    }

    /**
     * PDFに備考を設定数.
     *
     * @param array $formData
     */
    protected function renderEtcData(array $formData)
    {
        // フォント情報のバックアップ
        $this->backupFont();

        $this->Cell(0, 10, '', 0, 1, 'C', 0, '');

        $this->SetFont(self::FONT_GOTHIC, 'B', 9);
        $this->MultiCell(0, 6, '＜ 備考 ＞', 'T', 2, 'L', 0, '');

        $this->SetFont(self::FONT_SJIS, '', 8);

        $this->Ln();
        // rtrimを行う
        $text = preg_replace('/\s+$/us', '', $formData['note1']."\n".$formData['note2']."\n".$formData['note3']);
        $this->MultiCell(0, 4, $text, '', 2, 'L', 0, '');

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * タイトルをPDFに描画する.
     *
     * @param string $title
     */
    protected function renderTitle($title)
    {
        // 基準座標を設定する
        $this->setBasePosition();

        // フォント情報のバックアップ
        $this->backupFont();

       //文書タイトル（納品書・請求書）
        $this->SetFont(self::FONT_GOTHIC, '', 15);
        $this->Cell(0, 10, $title, 0, 2, 'C', 0, '');
        $this->Cell(0, 66, '', 0, 2, 'R', 0, '');
        $this->Cell(5, 0, '', 0, 0, 'R', 0, '');

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 購入者情報を設定する.
     *
     * @param Order $Order
     */
    protected function renderOrderData(Order $Order)
    {
        // 基準座標を設定する
        $this->setBasePosition();

        // フォント情報のバックアップ
        $this->backupFont();

        // =========================================
        // 購入者情報部
        // =========================================
        // 郵便番号
        $text = '〒 '.$Order->getZip01().' - '.$Order->getZip02();
        $this->lfText(23, 43, $text, 10);

        // 購入者都道府県+住所1
        $text = $Order->getPref().$Order->getAddr01();
        $this->lfText(27, 47, $text, 10);
        $this->lfText(27, 51, $Order->getAddr02(), 10); //購入者住所2

        // 購入者氏名
        $text = $Order->getName01().'　'.$Order->getName02().'　様';
        $this->lfText(27, 59, $text, 11);

        // =========================================
        // お買い上げ明細部
        // =========================================
        $this->SetFont(self::FONT_SJIS, '', 10);

        //ご注文日
        $orderDate = $Order->getCreateDate()->format('Y/m/d H:i');
        if ($Order->getOrderDate()) {
            $orderDate = $Order->getOrderDate()->format('Y/m/d H:i');
        }

        $this->lfText(25, 125, $orderDate , 10);
        //注文番号
        $this->lfText(25, 135, $Order->getId(), 10);

        // 総合計金額
        $this->SetFont(self::FONT_SJIS, 'B', 15);
        $paymentTotalText = number_format($Order->getPaymentTotal()).' '.self::MONETARY_UNIT;

        $this->setBasePosition(120, 95.5);
        $this->Cell(5, 7, '', 0, 0, '', 0, '');
        $this->Cell(67, 8, $paymentTotalText, 0, 2, 'R', 0, '');
        $this->Cell(0, 45, '', 0, 2, '', 0, '');

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 購入商品詳細情報を設定する.
     *
     * @param Order $Order
     */
    protected function renderOrderDetailData(Order $Order)
    {
        $arrOrder = array();
        // テーブルの微調整を行うための購入商品詳細情報をarrayに変換する

        // =========================================
        // 受注詳細情報
        // =========================================
        $i = 0;
        /* @var OrderDetail $OrderDetail */
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // class categoryの生成
            $classCategory = '';
            if ($OrderDetail->getClassCategoryName1()) {
                $classCategory .= ' [ '.$OrderDetail->getClassCategoryName1();
                if ($OrderDetail->getClassCategoryName2() == '') {
                    $classCategory .= ' ]';
                } else {
                    $classCategory .= ' * '.$OrderDetail->getClassCategoryName2().' ]';
                }
            }
            // 税
            $tax = $this->app['eccube.service.tax_rule']->calcTax($OrderDetail->getPrice(), $OrderDetail->getTaxRate(), $OrderDetail->getTaxRule());
            $OrderDetail->setPriceIncTax($OrderDetail->getPrice() + $tax);

            // product
            $arrOrder[$i][0] = sprintf('%s / %s / %s', $OrderDetail->getProductName(), $OrderDetail->getProductCode(), $classCategory);
            // 購入数量
            $arrOrder[$i][1] = number_format($OrderDetail->getQuantity());
            // 税込金額（単価）
            $arrOrder[$i][2] = number_format($OrderDetail->getPriceIncTax()).self::MONETARY_UNIT;
            // 小計（商品毎）
            $arrOrder[$i][3] = number_format($OrderDetail->getTotalPrice()).self::MONETARY_UNIT;

            ++$i;
        }

        // =========================================
        // 小計
        // =========================================
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '';
        $arrOrder[$i][3] = '';

        ++$i;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '商品合計';
        $arrOrder[$i][3] = number_format($Order->getSubtotal()).self::MONETARY_UNIT;

        ++$i;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '送料';
        $arrOrder[$i][3] = number_format($Order->getDeliveryFeeTotal()).self::MONETARY_UNIT;

        ++$i;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '手数料';
        $arrOrder[$i][3] = number_format($Order->getCharge()).self::MONETARY_UNIT;

        ++$i;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '値引き';
        $arrOrder[$i][3] = '- '.number_format($Order->getDiscount()).self::MONETARY_UNIT;

        ++$i;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '請求金額';
        $arrOrder[$i][3] = number_format($Order->getPaymentTotal()).self::MONETARY_UNIT;

        // PDFに設定する
        $this->setFancyTable($this->labelCell, $arrOrder, $this->widthCell);
    }

    /**
     * PDFへのテキスト書き込み
     *
     * @param int    $x     X座標
     * @param int    $y     Y座標
     * @param string $text  テキスト
     * @param int    $size  フォントサイズ
     * @param string $style フォントスタイル
     */
    protected function lfText($x, $y, $text, $size = 0, $style = '')
    {
        // 退避
        $bakFontStyle = $this->FontStyle;
        $bakFontSize = $this->FontSizePt;

        $this->SetFont('', $style, $size);
        $this->Text($x + $this->baseOffsetX, $y + $this->baseOffsetY, $text);

        // 復元
        $this->SetFont('', $bakFontStyle, $bakFontSize);
    }

    /**
     * Colored table.
     *
     * TODO: 後の列の高さが大きい場合、表示が乱れる。
     *
     * @param array $header 出力するラベル名一覧
     * @param array $data   出力するデータ
     * @param array $w      出力するセル幅一覧
     */
    protected function setFancyTable($header, $data, $w)
    {
        // フォント情報のバックアップ
        $this->backupFont();

        // 開始座標の設定
         $this->setBasePosition(0, 149);

        // Colors, line width and bold font
        $this->SetFillColor(216, 216, 216);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont(self::FONT_SJIS, 'B', 8);
        $this->SetFont('', 'B');

        // Header
        $this->Cell(5, 7, '', 0, 0, '', 0, '');
        $count = count($header);
        for ($i = 0; $i < $count; ++$i) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();

        // Color and font restoration
        $this->SetFillColor(235, 235, 235);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = 0;
        $h = 4;
        foreach ($data as $row) {
            // 行のの処理
            $i = 0;
            $h = 4;
            $this->Cell(5, $h, '', 0, 0, '', 0, '');

            // Cellの高さを保持
            $cellHeight = 0;
            foreach ($row as $col) {
                // 列の処理
                // TODO: 汎用的ではない処理。この指定は呼び出し元で行うようにしたい。
                // テキストの整列を指定する
                $align = ($i == 0) ? 'L' : 'R';

                // セル高さが最大値を保持する
                if ($h >= $cellHeight) {
                    $cellHeight = $h;
                }

                // 最終列の場合は次の行へ移動
                // (0: 右へ移動(既定)/1: 次の行へ移動/2: 下へ移動)
                $ln = ($i == (count($row) - 1)) ? 1 : 0;

                $this->MultiCell(
                    $w[$i],             // セル幅
                    $cellHeight,        // セルの最小の高さ
                    $col,               // 文字列
                    1,                  // 境界線の描画方法を指定
                    $align,             // テキストの整列
                    $fill,              // 背景の塗つぶし指定
                    $ln                 // 出力後のカーソルの移動方法
                );
                $h = $this->getLastH();

                ++$i;
            }
            $fill = !$fill;
        }
        $this->Cell(5, $h, '', 0, 0, '', 0, '');
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->SetFillColor(255);

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 基準座標を設定する.
     *
     * @param int $x
     * @param int $y
     */
    protected function setBasePosition($x = null, $y = null)
    {
        // 現在のマージンを取得する
        $result = $this->getMargins();

        // 基準座標を指定する
        $actualX = is_null($x) ? $result['left'] : $x;
        $this->SetX($actualX);
        $actualY = is_null($y) ? $result['top'] : $y;
        $this->SetY($actualY);
    }

    /**
     * データが設定されていない場合にデフォルト値を設定する.
     *
     * @param array $formData
     */
    protected function setDefaultData(array &$formData)
    {
        $defaultList = array(
            'title' => $this->app->trans('admin.plugin.order_pdf.title.default'),
            'message1' => $this->app->trans('admin.plugin.order_pdf.message1.default'),
            'message2' => $this->app->trans('admin.plugin.order_pdf.message2.default'),
            'message3' => $this->app->trans('admin.plugin.order_pdf.message3.default'),
        );

        foreach ($defaultList as $key => $value) {
            if (is_null($formData[$key])) {
                $formData[$key] = $value;
            }
        }
    }

    /**
     * Font情報のバックアップ.
     */
    protected function backupFont()
    {
        // フォント情報のバックアップ
        $this->bakFontFamily = $this->FontFamily;
        $this->bakFontStyle = $this->FontStyle;
        $this->bakFontSize = $this->FontSizePt;
    }

    /**
     * Font情報の復元.
     */
    protected function restoreFont()
    {
        $this->SetFont($this->bakFontFamily, $this->bakFontStyle, $this->bakFontSize);
    }
}
