# 帳票出力プラグイン
[![Build Status](https://travis-ci.org/eccubevn/order-pdf-plugin.svg?branch=order-pdf-renew)](https://travis-ci.org/eccubevn/order-pdf-plugin)
[![Build status](https://ci.appveyor.com/api/projects/status/f4pdw9riykju7xlf/branch/order-pdf-renew?svg=true)](https://ci.appveyor.com/project/lqdung-lockon/order-pdf-plugin/branch/order-pdf-renew)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/373cb7b3-9b1d-405e-b9a4-f6a90e97ddc2/mini.png)](https://insight.sensiolabs.com/projects/373cb7b3-9b1d-405e-b9a4-f6a90e97ddc2)
[![Coverage Status](https://coveralls.io/repos/github/eccubevn/order-pdf-plugin/badge.svg?branch=order-pdf-renew)](https://coveralls.io/github/eccubevn/order-pdf-plugin?branch=order-pdf-renew)

## 概要
納品書をPDFで出力することができるプラグイン。 

## フロント
機能なし

## 管理画面
### 指定した受注の納品書をPDFで出力できる。
- 受注マスターで受注を選択後、帳票出力ボタン押すと納品書をPDFで出力できる。
- 複数の受注を選択して、一括で納品書を出力することができる。

### 納品書の記載内容をカスタマイズできる。
- 納品書の記載内容をカスタマイズことできる。
    - 発行日
    - 納品書タイトル
    - メッセージ
    - 備考
- カスタマイズ内容を保存して、次回のデフォルトにすることができる。

## オプション
### ロゴファイルをカスタマイズすることができる。
- `app/template/admin/OrderPdf/logo.png`を変更することで、ロゴマークをカスタマイズできる。
