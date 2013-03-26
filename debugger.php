<?php
/**
 * debugger (like ruby-debug)
 * usage:
 *      eval(Debugger::get());      # debug. stop and run any code on console.
 * NOTE:
 *      use only programmer who knows eval()'s lisk.
 */
#TODO: debuggerで起動していない場合は、Debugger.get() ではパラメータ値などを出力したい。
#      かつ、debugger利用のためのパラメータLoadを自動で行うスクリプトを生成したい。
#TODO: readline の導入
#TODO: command 一覧 (help?) の表示
echo "load debugger....\n";
class Debugger
{
    function get()
    {
        if (!defined('STDIN')) {     // 
            #TODO: ここで、Webサーバから起動した時のパラメータ値などを出力する
            return "/* donothing */";
        }

        // CLIで実行された
        $proc = '
        Debugger::printPosition(__FILE__);
        for ($i=1; $i<100; $i++) {       // 安全装置つき
            $handle = Debugger::handleInput(__FILE__);
            if ($handle == "break") {
                break;
            } else if (empty($handle)) {
                continue;   // 何もしない
            }
            eval($handle);
            echo "\n";
        }
        ';
        return $proc;
    }

    function handleInput($file = null)
    {
        static $i = 0;
        echo 'phpc('.++$i.') >> ';
        $input = rtrim(fgets(STDIN), "\n"); // 末尾改行不要
        if ($input == "exit") {
            echo "終了します\n";
            exit;
#        } else if (preg_match("/\\p/", $input, $matched)) {
        } else if ($input == "\\p") {
            if (isset($file)) {
                Debugger::printPosition($file); #, $matched[1], $matched[2]);
            } else {
#                echo "\033[1;31m". '現在地表示できません' ."\033[0m\n";
                cecho('現在地表示できません', 'red');
#                echo _color('現在地表示できません'). "\n";
            }
            return null;
        } else if ($input == "\\q") {
            return 'break';
        } else if (empty($input)) { return null;    // 何もしない
        } else {
            return $input;
        }
    }

    function printPosition($evaled_file, $back = 5, $forward = 5)
    {
        $temp = explode(':', $evaled_file);
        $file = $temp[0];

        preg_match('/(^[^\(]*)\((\d+)\)/', $file, $matched);
        $file_path = $matched[1];
        $current_line = $matched[2];

        $contents = Debugger::getDebuggingFileContents(
            $file_path, $current_line - $back, $current_line + $forward);

        echo "\nDEBUGGER -- {$file_path} --\n";
        foreach ($contents as $line=>$value) {
            $mark = $line == $current_line ? '>' : ' ';
            echo " {$mark} {$value}";
        }
    }

    function getDebuggingFileContents($file, $read_from, $read_to)
    {
        $fp = @fopen($file, 'r');
        if (!$fp) return null;

        $contents = array();
        for ($i = 1; $line = fgets($fp); $i++) {
            if ($read_from <= $i && $i <= $read_to) {
                $contents[$i] = $line;
            }
        }
        @fclose($fp);
        return $contents;
    }

    /**
     * ./debug_[日時].php などの形式で、デバッグ用のPHPスクリプトを生成する
     */
    function outputDebuggerScript()
    {
    }

    function getParameterStrings()
    {
        $params = '$_REQUEST = ' . var_export($_REQUEST, 1);


        return $params;
    }
}
function cecho($msg, $color = null)
{
    $colors = array('red' => 31);
    if (array_key_exists($color, $colors)) {
        echo "\033[1;{$colors[$color]}m{$msg}\033[0m\n";
    } else {
        echo "{$msg}\n";
    }
}

echo " --- パラメータ等を入力する\n";
echo <<<MEMO
  ex.
  \$_REQUEST['?????'] = 'hogehoge';

MEMO;
for ($i=1; $i<100; $i++) {       // 安全装置つき
    $handle = Debugger::handleInput();
    if ($handle == "break") {
        break;
    } else if (empty($handle)) {
        continue;   // 何もしない
    }
    eval($handle);
    echo "\n";
}

array_shift($argv);     // 実行時引数をShiftしておく。
echo 'execute debugger for > ' . join(' ', $argv) . "\n";
require_once $argv[0];

