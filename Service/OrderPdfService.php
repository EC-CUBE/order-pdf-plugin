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

namespace Plugin\OrderPdf\Service;

use Eccube\Application;
use Plugin\OrderPdf\Service;

class OrderPdfService extends AbstractFPDIService
{
    // ====================================
    // 定数宣言
    // ====================================
    /** ORderPdf用リポジトリ名 */
    const REPOSITORY_ORDER_PDF = 'eccube.plugin.order_pdf.repository.order_pdf_order';

    /** 通貨単位 */
    const MONETARY_UNIT = '円';

    /** ダウンロードするPDFファイルのデフォルト名 */
    const DEFAULT_PDF_FILE_NAME = 'nouhinsyo.pdf';

    /** FONT ゴシック */
    const FONT_GOTHIC = 'kozgopromedium';
    /** FONT 明朝 */
    const FONT_SJIS = 'kozminproregular';

    /** PDFテンプレートファイル名 */
    const PDF_TEMPLATE_FILE_PATH = '/../Resource/template/nouhinsyo1.pdf';

    // ====================================
    // 変数宣言
    // ====================================
    /** @var \Eccube\Application */
    public $app;

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    /*** 購入詳細情報 ラベル配列 @var array */
    private $labelCell = array();

    /*** 購入詳細情報 幅サイズ配列 @var array */
    private $widthCell = array();

    /** 最後に処理した注文番号 @var unknown */
    private $lastOrderId = null;

    /** 処理する注文番号件数 @var unknown */
    private $orderIdCnt = 0;

    // --------------------------------------
    // Font情報のバックアップデータ
    /** @var unknown フォント名 */
    private $bakFontFamily;
    /** @var unknown フォントスタイル */
    private $bakFontStyle;
    /** @var unknown フォントサイズ */
    private $bakFontSize;
    // --------------------------------------

    // lfTextのoffset
    private $baseOffsetX = 0;
    private $baseOffsetY = -4;

    /** ダウンロードファイル名 @var unknown */
    private $downloadFileName = null;

    /** 発行日 @var unknown */
    private $issueDate = "";

    /**
     * Font情報のバックアップ
     */
    protected function backupFont() {
        // フォント情報のバックアップ
        $this->bakFontFamily = $this->FontFamily;
        $this->bakFontStyle = $this->FontStyle;
        $this->bakFontSize = $this->FontSizePt;
    }

    /**
     * Font情報の復元
     */
    protected function restoreFont() {
        $this->SetFont($this->bakFontFamily, $this->bakFontStyle, $this->bakFontSize);
    }

    /**
     * コンストラクタ
     * @param Application $app
     */
    public function __construct(Application $app)
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
        $this->widthCell = array(110.3,12,21.7,24.5);

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
     *                  [KEY]
     *                      ids: 注文番号
     *                      issue_date: 発行日
     *                      title: タイトル
     *                      message1: メッセージ1行目
     *                      message2: メッセージ2行目
     *                      message3: メッセージ3行目
     *                      note1: 備考1行目
     *                      note2: 備考2行目
     *                      note3: 備考3行目
     * @return boolean
     */
    public function makePdf(array $formData) {
        // 発行日の設定
        $this->issueDate = '作成日: ' . $formData['issue_date']->format('Y年m月d日');
        // ダウンロードファイル名の初期化
        $this->downloadFileName = null;

        // データが空であれば終了
        if('' === $formData['ids'] || is_null($formData['ids'])) {
            return false;
        }

        // 注文番号をStringからarrayに変換
        $ids = explode(',', $formData['ids']);

        // 注文番号の総件数を保持する
        $this->orderIdCnt = count($ids);

        // 空文字列の場合のデフォルトメッセージを設定する
        $this->setDefaultData($formData);

        // テンプレートファイルを読み込む
        $templateFilePath =  __DIR__ . self::PDF_TEMPLATE_FILE_PATH;
        $this->setSourceFile($templateFilePath);

        foreach ($ids as $id) {
            $this->lastOrderId = $id;

            // 注文番号から受注情報を取得する
            $order = $this->app[self::REPOSITORY_ORDER_PDF]->find($id);
            if(is_null($order)) {
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
     * @return
     */
    public function outputPdf() {
         return $this->Output($this->getPdfFileName(), "S");
    }

    /**
     * PDFファイル名を取得する
     * PDFが1枚の時は注文番号をファイル名につける
     * @return string ファイル名
     */
    public function getPdfFileName() {
        if(!is_null($this->downloadFileName)) {
            return $this->downloadFileName;
        }
        $this->downloadFileName = self::DEFAULT_PDF_FILE_NAME;
        if($this->PageNo() == 1) {
            $this->downloadFileName = 'nouhinsyo-No' . $this->lastOrderId . '.pdf';
        }
        return $this->downloadFileName;
    }

    /**
     * フッターに発行日を出力する
     */
    public function Footer() {
        $this->Cell(0, 0, $this->issueDate, 0, 0, 'R');
    }
    /**
     * 作成するPDFのテンプレートファイルを指定する
     */
    protected function addPdfPage() {
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
     *
     */
    protected function renderShopData() {
        // 基準座標を設定する
        $this->setBasePosition();

        // 特定商取引法を取得する
        $Help = $this->app['eccube.plugin.order_pdf.repository.order_pdf_help']->get();

        // ショップ名
        $this->lfText(125, 60, $this->BaseInfo['shop_name'], 8, 'B');
        // URL
        $this->lfText(125, 63, $Help->getLawUrl(), 8);
        // 会社名
        $this->lfText(125, 68, $Help->getLawCompany(), 8);
        // 郵便番号
        $text = '〒 ' . $Help->getLawZip01() . ' - ' . $Help->getLawZip02();
        $this->lfText(125, 71, $text, 8);
        // 都道府県+所在地
        $lawPref = is_null($Help->getLawPref()) ? null : $Help->getLawPref()->getName();
        $text = $lawPref . $Help->getLawAddr01();
        $this->lfText(125, 74, $text, 8);
        $this->lfText(125, 77, $Help->getLawAddr02(), 8);

        // 電話番号
        $text = 'TEL: ' . $Help->getLawTel01() . '-' . $Help->getLawTel02() . '-' . $Help->getLawTel03();

        //FAX番号が存在する場合、表示する
        if (strlen($Help->getLawFax01()) > 0) {
            $text .= '　FAX: ' . $Help->getLawFax01() . '-' . $Help->getLawFax02() . '-' . $Help->getLawFax03();
        }
        $this->lfText(125, 80, $text, 8);  //TEL・FAX

        // メールアドレス
        if (strlen($Help->getLawEmail()) > 0) {
            $text = 'Email: '.$Help->getLawEmail();
            $this->lfText(125, 83, $text, 8);      //Email
        }
        // ロゴ画像
        $logoFilePath =  __DIR__ . '/../Resource/template/logo.png';
        $this->Image($logoFilePath, 124, 46, 40);
    }

    /**
     * メッセージを設定する
     * @param array $formData
     */
    protected function renderMessageData(array $formData) {
        $this->lfText(27, 70, $formData['message1'], 8);  //メッセージ1
        $this->lfText(27, 74, $formData['message2'], 8);  //メッセージ2
        $this->lfText(27, 78, $formData['message3'], 8);  //メッセージ3
    }

    /**
     * PDFに備考を設定数
     * @param array $formData
     */
    protected function renderEtcData(array $formData) {
        // フォント情報のバックアップ
        $this->backupFont();

        $this->Cell(0, 10, '', 0, 1, 'C', 0, '');

        $this->SetFont(self::FONT_GOTHIC, 'B', 9);
        $this->MultiCell(0, 6, '＜ 備考 ＞', 'T', 2, 'L', 0, '');

        $this->SetFont(self::FONT_SJIS, '', 8);

        $this->ln();
        // rtrimを行う
        $text = preg_replace('/\s+$/us', '', $formData['note1'] . "\n" .$formData['note2'] . "\n" . $formData['note3']);
        $this->MultiCell(0, 4, $text, '', 2, 'L', 0, '');

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * タイトルをPDFに描画する.
     *
     * @param string $title
     */
    protected function renderTitle($title) {
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
     * 購入者情報を設定する
     *
     * @param \Plugin\OrderPdf\Entity\OrderPdfOrder $order
     */
    protected function renderOrderData(\Plugin\OrderPdf\Entity\OrderPdfOrder $order) {
        // 基準座標を設定する
        $this->setBasePosition();

        // フォント情報のバックアップ
        $this->backupFont();

        // =========================================
        // 購入者情報部
        // =========================================
        // 郵便番号
        $text = '〒 '.$order->getZip01() . ' - ' . $order->getZip02();
        $this->lfText(23, 43, $text, 10);

        // 購入者都道府県+住所1
        $text = $order->getPref() . $order->getAddr01();
        $this->lfText(27, 47, $text, 10);
        $this->lfText(27, 51, $order->getAddr02(), 10); //購入者住所2

        // 購入者氏名
        $text = $order->getName01() . '　' . $order->getName02() . '　様';
        $this->lfText(27, 59, $text, 11);

        // =========================================
        // お買い上げ明細部
        // =========================================
        $this->SetFont(self::FONT_SJIS, '', 10);

        //ご注文日
        $this->lfText(25, 125, $order->getCreateDate()->format('Y/m/d H:i'), 10);
        //注文番号
        $this->lfText(25, 135, $order->getId(), 10);

        // 総合計金額
        $this->SetFont(self::FONT_SJIS, 'B', 15);
        $paymentTotalText =  number_format($order->getPaymentTotal()).' ' . self::MONETARY_UNIT;

        $this->setBasePosition(120, 95.5);
        $this->Cell(5, 7, '', 0, 0, '', 0, '');
        $this->Cell(67, 8, $paymentTotalText, 0, 2, 'R', 0, '');
        $this->Cell(0, 45, '', 0, 2, '', 0, '');

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 購入商品詳細情報を設定する
     *
     * @param \Plugin\OrderPdf\Entity\OrderPdfOrder $order
     */
    protected function renderOrderDetailData($order) {
        $arrOrder = array();
        // テーブルの微調整を行うための購入商品詳細情報をarrayに変換する

        // =========================================
        // 受注詳細情報
        // =========================================
        $i = 0;
        foreach ($order->getOrderDetails() as $orderDetail) {
            // class categoryの生成
             $classcategory = "";
             if ($orderDetail->getClassCategoryName1()) {
                 $classcategory .= ' [ ' . $orderDetail->getClassCategoryName1();
                 if ($orderDetail->getClassCategoryName2() == '') {
                     $classcategory .= ' ]';
                 } else {
                     $classcategory .= ' * ' . $orderDetail->getClassCategoryName2() . ' ]';
                 }
             }

             // 税
             $tax = $this->app['eccube.service.tax_rule']
                 ->calcTax($orderDetail->getPrice(), $orderDetail->getTaxRate(), $orderDetail->getTaxRule());
             $orderDetail->setPriceIncTax($orderDetail->getPrice() + $tax);

             // product
             $arrOrder[$i][0] = sprintf('%s / %s / %s', $orderDetail->getProductName(), $orderDetail->getProductCode(), $classcategory);;
             // 購入数量
             $arrOrder[$i][1] = number_format($orderDetail->getQuantity());
             // 税込金額（単価）
             $arrOrder[$i][2] = number_format($orderDetail->getPriceIncTax()) . self::MONETARY_UNIT;
             // 小計（商品毎）
             $arrOrder[$i][3] = number_format($orderDetail->getTotalPrice()) . self::MONETARY_UNIT;

             $i++;
        }

        // =========================================
        // 小計
        // =========================================
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '';
        $arrOrder[$i][3] = '';

        $i++;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '商品合計';
        $arrOrder[$i][3] = number_format($order->getSubtotal()) . self::MONETARY_UNIT;

        $i++;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '送料';
        $arrOrder[$i][3] = number_format($order->getDeliveryFeeTotal()) . self::MONETARY_UNIT;

        $i++;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '手数料';
        $arrOrder[$i][3] = number_format($order->getCharge()) . self::MONETARY_UNIT;

        $i++;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '値引き';
        $arrOrder[$i][3] = '- '.number_format($order->getDiscount()) . self::MONETARY_UNIT;

        $i++;
        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '';
        $arrOrder[$i][2] = '請求金額';
        $arrOrder[$i][3] = number_format($order->getPaymentTotal()).self::MONETARY_UNIT;

        // PDFに設定する
        $this->FancyTable($this->labelCell, $arrOrder, $this->widthCell);


    }

    /**
     * PDFへのテキスト書き込み
     *
     * @param unknown $x X座標
     * @param unknown $y Y座標
     * @param unknown $text テキスト
     * @param number $size フォントサイズ
     * @param string $style フォントスタイル
     */
    protected function lfText($x, $y, $text, $size = 0, $style = '')
    {
        // 退避
        $bak_font_style = $this->FontStyle;
        $bak_font_size = $this->FontSizePt;

        $this->SetFont('', $style, $size);
        $this->Text($x + $this->baseOffsetX, $y + $this->baseOffsetY, $text);

        // 復元
        $this->SetFont('', $bak_font_style, $bak_font_size);
    }

    /**
     * Colored table
     *
     * FIXME: 後の列の高さが大きい場合、表示が乱れる。
     *
     * @param unknown $header 出力するラベル名一覧
     * @param unknown $data 出力するデータ
     * @param unknown $w 出力するセル幅一覧
     */
    protected function FancyTable($header, $data, $w) {
        // フォント情報のバックアップ
        $this->backupFont();

        // 開始座標の設定
         $this->setBasePosition(0,149);

        // Colors, line width and bold font
        $this->SetFillColor(216, 216, 216);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont(self::FONT_SJIS, 'B', 8);
        $this->SetFont('', 'B');

        // Header
        $this->Cell(5, 7, '', 0, 0, '', 0, '');
        for ($i = 0; $i < count($header); $i++) {
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
                // FIXME 汎用的ではない処理。この指定は呼び出し元で行うようにしたい。
                // テキストの整列を指定する
                $align = ($i == 0) ? 'L' : 'R';

                // セル高さが最大値を保持する
                if ($h >= $cellHeight) {
                    $cellHeight = $h;
                }

                // 最終列の場合は次の行へ移動
                // (0: 右へ移動(既定)/1: 次の行へ移動/2: 下へ移動)
                $ln = ($i == (count($row) - 1)) ? 1 : 0;

                $h = $this->MultiCell(
                        $w[$i],             // セル幅
                        $cellHeight,        // セルの最小の高さ
                        $col,               // 文字列
                        1,                  // 境界線の描画方法を指定
                        $align,             // テキストの整列
                        $fill,              // 背景の塗つぶし指定
                        $ln                 // 出力後のカーソルの移動方法
                     );
                $h = $this->getLastH();

                $i++;
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
     * 基準座標を設定する
     * @param unknown $x
     * @param unknown $y
     */
    protected function setBasePosition($x = null, $y = null) {
        // 現在のマージンを取得する
        $result = $this->getMargins();

        // 基準座標を指定する
        $this->setX(is_null($x) ? $result['left'] : $x);
        $this->setY(is_null($y) ? $result['top']: $y);
    }

    /**
     * データが設定されていない場合にデフォルト値を設定する.
     *
     * @param unknown $formData
     */
    protected function setDefaultData(&$formData) {

        $defaultList = array(
            'title' => 'お買上げ明細書(納品書)',
            'message1' => 'このたびはお買上げいただきありがとうございます。',
            'message2' => '下記の内容にて納品させていただきます。',
            'message3' => 'ご確認くださいますよう、お願いいたします。',
        );

        foreach($defaultList as $key => $value) {
            if(is_null($formData[$key])) {
                $formData[$key] = $value;
            }
        }
    }

}
