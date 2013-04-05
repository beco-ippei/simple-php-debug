simple-php-debug
=========

PHP-Debugger made by only php. Without use var_dump() and echo() and any browser print functions.

Inspired from Ruby's "ruby-debug" and Rails's Rails-console(railsc) or @jugyo's "ir_b".
They are able to run any code on any-where with command-prompt.

This script can use only 1 file, 'debugger.php' (may be at future).
Create soft link or copy this file to your project directory.


TODO
------

* header() が呼ばれた時に、エラーになるのを回避する (優先度：高)
* エラーハンドリング (優先度: 高)
* debuggerで起動していない場合は、Debugger.get() ではパラメータ値などを出力したい。
 かつ、debugger利用のためのパラメータLoadを自動で行うスクリプトを生成したい。
 起動後に、パラメータを選択してdebuggerを起動できる補完ができる、とか。
* readline の導入
  補完も実装。変数の補完の場合には、GLOBALSのマージもしておきたい。
* command 一覧 (help?) の表示
* 終了時に再度コマンド待ちになる。
* 元々の標準出力を退避して、後で見れるように、とか。
* プロンプトに、現在地のざっくりとした情報を見せる (Debuggerのスタック情報?)
* remove japanese coment and message.
* get current file and start up point from Backtrace info.


Usage (ja)
-------
デバッガーを起動したい箇所に、以下のコードを記述します。
```php
eval(Debugger::get());
```

実行したいプログラム(例：test/sample.php)を引数に指定して、debugger.php を実行します。
```
> php debugger.php test/sample.php
```
最初に prepare モードでpromptが起動します。
```
load debugger....
-- input parmeters as global variables ($_REQUEST or $_COOKIE or else)
  ex.  $_REQUEST['?????'] = 'hogehoge';

Commands
  \query [query-strings]
        parse query-strings to $_REQUEST
        ex. "\query id=234&type=hoge&flag=1
        -> $_REQUEST == array('id'=>234, 'type'=>'hoge', 'flag'=>1)
  \q    finish prepare and run codes.

  if ready, type '\q'
phpc(1) >> 
```
コマンドのヘルプとプロンプトが表示されます。

ここでは、ブラウザからのRequestデータを自分で入力して生成してメインのプログラム実行に備えます。
例）POSTで送信されるパラメータをセットする。
```
phpc(1) >> $_REQUEST['id'] = 'beco-ippei'; [Enter]
phpc(2) >> $_REQUEST['password'] = 'hogehoge'; [Enter]
phpc(3) >> $_REQUEST['page'] = 'login'; [Enter]
```
=> ログイン時に、id='beco-ippei' と入力し、 password='hogehoge' と入力したことと同等の動きをさせる。
ここでの入力値は、$_REQUEST や $_COOKIE などのGLOBAL変数に限られます。
（GLOBAL変数以外は、prepare処理の変数のスコープの問題により、メインのプログラム実行時には無視されます）

入力した値や環境に依存する値を確認したい場合は、var_dump()などで標準出力で確認できます。
```
phpc(4) >> var_dump($_REQUEST);
array(3) {
  ["id"]=>
  string(10) "beco-ippei"
  ["password"]=>
  string(8) "hogehoge"
  ["page"]=>
  string(5) "login"
}
```

GETで送信されたパラメータを指定したい場合は、"\query"コマンドを使います。
```
phpc(5) >> \query page=video&id=12345&full_screen=on [Enter]
phpc(6) >> var_dump($_REQUEST); [Enter]
array(3) {
  ["page"]=>
  string(5) "video"
  ["id"]=>
  string(5) "12345"
  ["full_screen"]=>
  string(2) "on"
}
```

準備が完了し、メインのプログラムを実行できる状態になったら、prepareモードを抜けます。
引数で指定したプログラムが実行され、ブレイクポイント(```eval(Debugger::get())```)でデバッガが起動します。
```
phpc(5) >> \q
execute debugger for > test/sample.php
# この辺りは、"test/sample.php"の実行結果があれば、何か表示されたりします。 ############
PHP Warning:  Cannot modify header information - headers already sent by (output started at /work/dev/simple-php-debug/debugger.php:
4) in /work/dev/simple-php-debug/test/sample.php on line 6
# 実行結果ここまで ###########################

DEBUGGER -- /[path-to-execute-file]/test/sample.php --
   
   $_REQUEST['hoge'] = 'fooooooo';
   
   header('Cookie: hoge');
   
 > eval(Debugger::get());
   var_dump($_REQUEST);
   
   if (array_key_exists('id', $_REQUEST)) {
       $id = $_REQUEST['id'];
       echo "id = ${id} !!!!\n";
phpc(1) >> 
```
上の例では、```header('Cookie: hoge');```の次のコードで止まって、プロンプトが起動します。

変数のスコープは```eval()```の実行と同じになり、任意のコードを入力できます。
```
phpc(2) >> $hoge = 'なにか書いてみる';
phpc(3) >> $_REQUEST['hoge'] = "hoge_value is {$hoge}";
phpc(4) >> var_dump($_REQUEST);
array(4) {
  ["page"]=>
  string(5) "video"
  ["id"]=>
  string(5) "12345"
  ["full_screen"]=>
  string(2) "on"
  ["hoge"]=>
  string(22) "hoge_value is なにか書いてみる"
}
```

その場で新しいファイルを読み込んで実行することもできます。
```
phpc(5) >> require_once('lib/DB.php');
phpc(6) >> $result = DB::query("SELECT * FROM users WHERE id = '{$_REQUEST['id']}'");
phpc(7) >> var_dump($result);
array(3) {
  ["id"]=>
  int(12345)
  ["password"]=>
  string(24) "hogehogehogehogehogehoge"
  ["name"]=>
  string(5) "beco-ippei"
}
```
prepareモード時と同様に、```\q```でデバッガを終了して以降の処理が再開されます。
入力した値は、デバッガを終了した後でも残ります。

すべての処理が終了すると、postCodeモードでデバッガが起動します。
処理結果を見たい場合などで利用してください。

```exit```を入力するか、```\q```で postCodeモードを終了し、デバッガの処理が終了します。


License
-------

Copyright © 2013 beco-ippei. simple-php-debug is licensed under the LGPL.
