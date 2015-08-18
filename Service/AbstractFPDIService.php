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

$includePath = get_include_path() . ";" . __DIR__ . '/../vendor/tcpdf';
$includePath = $includePath . ";" . __DIR__ . '/../vendor/FPDI';
set_include_path($includePath);

require_once(__DIR__ . '/../vendor/tcpdf/tcpdf.php');
require_once(__DIR__ . '/../vendor/FPDI/fpdi.php');


use Eccube\Application;

/**
 * FPDIのラッパークラス
 *
 */
abstract class AbstractFPDIService extends \FPDI
{
}
