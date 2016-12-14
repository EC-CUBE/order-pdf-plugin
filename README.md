# 帳票出力プラグイン
[![Build Status](https://travis-ci.org/EC-CUBE/order-pdf-plugin.svg?branch=master)](https://travis-ci.org/EC-CUBE/order-pdf-plugin)
[![Build status](https://ci.appveyor.com/api/projects/status/l5eoakt9828yorx9/branch/master?svg=true)](https://ci.appveyor.com/project/ECCUBE/order-pdf-plugin/branch/master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/671db746-85c5-4389-82e7-1a05fc35f9bc/mini.png)](https://insight.sensiolabs.com/projects/671db746-85c5-4389-82e7-1a05fc35f9bc)
[![Coverage Status](https://coveralls.io/repos/github/EC-CUBE/order-pdf-plugin/badge.svg?branch=master)](https://coveralls.io/github/EC-CUBE/order-pdf-plugin?branch=master)

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
