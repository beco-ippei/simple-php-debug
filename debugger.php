<?php
if (!Debugger::inCLI()) return;
echo "load debugger....\n";

register_shutdown_function('Debugger::postCode');
Debugger::prepare();

// 実行時引数をShiftしておく
array_shift($argv);
echo 'execute debugger for > ' . join(' ', $argv) . "\n";

$file = $argv[0];
chdir(dirname($file));

require_once basename($file);

exit(0);

/**
 * debugger (like ruby-debug)
 * usage:
 *      eval(Debugger::get());      # debug. stop and run any code on console.
 * NOTE:
 *   use only programmer who knows eval()'s lisk.
 *   support php version.4
 */
class Debugger
{
    /**
     * for static use
     * USAGE:
     * # any codes.....
     * // stop proccessing and wait input commands
     * eval(Debugger::get());
     * # any codes.....
     */
    function get()
    {
        if (!Debugger::inCLI()) {     // 
            #TODO: ここで、Webサーバから起動した時のパラメータ値などを出力する
            return "/* donothing */";
        }
        // CLIで実行された
        return Debugger::debuggerEvalCode(true);
    }

    function debuggerEvalCode($debugging = false)
    {
        $proc = "\$debugger = new Debugger({$debugging});\n";
        if ($debugging) {
            $proc .= "\$debugger->printPosition(__FILE__);\n";
        }
        $proc .= '
        set_error_handler("Debugger::handleError");
        for ($i=1; $i<100; $i++) {       // 安全装置つき
            $handle = $debugger->handleInput(__FILE__);
            if ($handle == "break") {
                break;
            } else if (empty($handle)) {
                continue;   // 何もしない
            }
            eval($handle);
            echo "\n";
        }
        restore_error_handler();
        ';
        return $proc;
    }

    /**
     * int $no, string $str [, string $file [, int $line [, array $context ]]]
     */
    function handleError($no, $str, $file = null, $line = null, $context = null)
    {
        $msg = "Error({$no}) {$str}";
        if (isset($file)) {
            $msg .= " at [{$file}]";
            if (isset($line)) $msg .= " on line {$line}";
        }

        echo Debugger::color("{$msg}\n");
#        if (isset($context)) {
#            var_dump($context);
#        }
        return true;
    }

    function historyFile()
    {
        return $_SERVER['HOME'].'/.simple_php_debug_history';
    }

    /**
     * for static use
     */
    function prepare()
    {
        Debugger::_readline_read_history();

        echo <<<MESSAGE
-- input parmeters as global variables (\$_REQUEST or \$_COOKIE or else)
  ex.  \$_REQUEST['?????'] = 'hogehoge';

Commands
  \query [query-strings]
        parse query-strings to \$_REQUEST
        ex. "\query id=234&type=hoge&flag=1
        -> \$_REQUEST == array('id'=>234, 'type'=>'hoge', 'flag'=>1)
  \q    finish prepare and run codes.

  if ready, type '\q'

MESSAGE;
        eval(Debugger::debuggerEvalCode());
    }

    /**
     * for static use
     * execute before debugger exit without error.
     */
    function postCode()
    {
        echo <<<MESSAGE
-- if watch global variables, you can exec any codes (\$_REQUEST or \$_COOKIE or else)
  ex.
  var_dump(\$_REQUEST);

  type '\q' or 'exit', exit debugger.

MESSAGE;
        eval(Debugger::debuggerEvalCode());
    }

    /**
     * for static use
     */
    function inCLI()
    {
        return defined('STDIN');
    }

    /**
     * constructor
     */
    function Debugger($debugging = false) { $this->__construct($debugging); }
    function __construct($debugging = false)
    {
        $this->debugging = $debugging;
        $this->i = 0;
    }

    function _readline($prompt)
    {
        $color = $this->debugging ? 'green' : 'cyan';
        $prompt = $this->color($prompt, $color);
        if (function_exists('readline')) {
            return readline($prompt);
        } else {
            echo $this->color($prompt, $color);
            return rtrim(fgets(STDIN), "\n");   // 末尾改行除去
        }
    }

    function _readline_read_history()
    {
        if (function_exists('readline_read_history')) {
            return readline_read_history(Debugger::historyFile());
        } else {
            # maybe do nothing...
        }
    }

    function _readline_add_history($line)
    {
        if (function_exists('readline_add_history')) {
            return readline_add_history($line);
        } else {
            #TODO: どうしよう・・・。
        }
    }

    function _readline_write_history()
    {
        if (function_exists('readline_write_history')) {
            return readline_write_history(Debugger::historyFile());
        } else {
            # maybe do nothing...
        }
    }

    #TODO コマンドの処理をもう少し綺麗にしたい
    function handleInput($file = null)
    {
        $input = $this->_readline('phpc('.++$this->i.') >> ');

        if ($input == "exit") {
            echo $this->color(".... 終了します\n", 'red');
            exit;
        } else if ($input == "\\p") {
            if (isset($file)) {
                $this->printPosition($file); #, $matched[1], $matched[2]);
            } else {
                echo $this->color('現在地表示できません', 'red'). "\n";
            }
            return null;
        } else if ($input == "\\q") {
            return 'break';
        } else if (preg_match('/^\\\query\s([^\s]+)/', $input, $matched)) {
            $this->_parseQueryString($matched[1]);
            return null;    // 処理済み
        } else if (empty($input)) {
            return null;    // 何もしない
        } else {
            $this->_readline_add_history($input);
            $this->_readline_write_history();
            return $input;
        }
    }

    function _parseQueryString($query)
    {
        $queries = explode('&', $query);
        foreach ($queries as $param) {
            list($key, $val) = explode('=', $param, 2);
            $_REQUEST[$key] = $val;
        }
        #NOTE: パラメータは後に来るもので上書き
    }

    function printPosition($evaled_file, $back = 5, $forward = 5)
    {
        $temp = explode(':', $evaled_file);
        $file = $temp[0];

        preg_match('/(^[^\(]*)\((\d+)\)/', $file, $matched);
        $file_path = $matched[1];
        $current_line = $matched[2];

        $contents = $this->getDebuggingFileContents(
            $file_path, $current_line - $back, $current_line + $forward);

        echo "\nDEBUGGER -- {$file_path} --\n";
        foreach ($contents as $line=>$value) {
            $mark = $line == $current_line ? $this->color('>', 'red') : ' ';
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
     * readline とかから、実行候補を選択できるようにしたいかなぁ。
     */
    function outputDebuggerScript()
    {
    }

    function getParameterStrings()
    {
        $params = '$_REQUEST = ' . var_export($_REQUEST, 1);


        return $params;
    }

    function color($msg, $color = null) {
        $colors = array(
            'black'     => 30,
            'red'       => 31,
            'green'     => 32,
            'brown'     => 33,
            'blue'      => 34,
            'purple'    => 35,
            'cyan'      => 36,
        );
        if (!array_key_exists($color, $colors)) return $msg;
        return "\033[1;{$colors[$color]}m{$msg}\033[0m";
    }
}
