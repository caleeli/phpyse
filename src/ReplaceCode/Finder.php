<?php
namespace ReplaceCode;

class Finder
{

    public $c = 0;
    protected $lastFilename = '';

    function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function folder($path)
    {
        foreach (glob($path . '/*.php') as $p) {
            call_user_func($this->callback, $p, $this);
        }
        foreach (glob($path . '/*', GLOB_ONLYDIR) as $p) {
            $this->folder($p);
        }
    }

    function getLineNumber($content, $offset)
    {
        return substr_count(substr($content, 0, $offset), "\n");
    }

    function getLine($content, $offset)
    {
        $i = strrpos($content, "\n", $offset - strlen($content));
        if (!$i)
            $i = 0;
        $j = strpos($content, "\n", $offset);
        if ($j) {
            return substr($content, $i + 1, $j - $i - 1);
        } else {
            return substr($content, $i + 1);
        }
    }

    function replaceLine($content, $lineNumber, $replacement)
    {
        $lines = explode("\n", $content);
        $lines[$lineNumber] = $replacement;
        return implode("\n", $lines);
    }

    function printFilenameOnce($filename)
    {
        if ($this->lastFilename != $filename) {
            echo "$filename\n";
        }
        $this->lastFilename = $filename;
    }
}
