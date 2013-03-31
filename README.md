simple-php-debug
=========

PHP-Debugger made by only php. Without use var_dump() and echo() and any browser print functions.


TODO
------

* debuggerで起動していない場合は、Debugger.get() ではパラメータ値などを出力したい。
 かつ、debugger利用のためのパラメータLoadを自動で行うスクリプトを生成したい。
 起動後に、パラメータを選択してdebuggerを起動できる補完ができる、とか。
* readline の導入
  補完も実装。変数の補完の場合には、GLOBALSのマージもしておきたい。
* command 一覧 (help?) の表示
* 終了時に再度コマンド待ちになる。
* 元々の標準出力を退避して、後で見れるように、とか。
* エラーハンドリング
* プロンプトに、現在地のざっくりとした情報を見せる (Debuggerのスタック情報?)
* remove japanese coment and message.
* get current file and start up point from Backtrace info.


License
-------

Copyright © 2013 beco-ippei. simple-php-debug is licensed under the LGPL.
