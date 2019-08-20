<?php declare(strict_types=1);


namespace Jeekens\Console\Output;


use function sort;
use function reset;
use function strpos;
use function sprintf;
use function implode;
use function is_array;
use function array_pop;
use function array_keys;
use function strtolower;
use function str_replace;
use function preg_replace;
use function array_unshift;
use function preg_match_all;
use function array_key_exists;

/**
 * Class Tags
 *
 * @package Jeekens\Console\Output
 */
class Tags
{

    protected $keys = [
        'bold' => 1,
        'dim' => 2,
        'italic' => 3,
        'underlined' => 4,
        'blink' => 5,
        'reverse' => 7,
        'hidden' => 8,
        'default' => 39,
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'purple' => 35,
        'cyan' => 36,
        'light_gray' => 37,
        'dark_grey' => 90,
        'light_red' => 91,
        'light_green' => 92,
        'light_yellow' => 93,
        'light_blue' => 94,
        'light_purple' => 95,
        'light_cyan' => 96,
        'white' => 97,
        'background_default' => 49,
        'background_black' => 40,
        'background_red' => 41,
        'background_green' => 42,
        'background_yellow' => 43,
        'background_blue' => 44,
        'background_purple' => 45,
        'background_cyan' => 46,
        'background_light_gray' => 47,
        'background_dark_grey' => 100,
        'background_light_red' => 101,
        'background_light_green' => 102,
        'background_light_yellow' => 103,
        "background_light_blue" => 104,
        'background_light_purple' => 105,
        'background_light_cyan' => 106,
        'background_white' => 107,
    ];

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var string
     */
    protected $regex = '';

    /**
     * @var string
     */
    protected $currentRegex = '';

    public function __construct()
    {
        $this->build();
    }

    public function regex()
    {
        if (empty($this->regex)){
            return ($this->regex = '(<(?:(?:(?:\\\)*\/)*(?:' . implode('|', array_keys($this->keys)) . '))>)is');
        }

        return $this->regex;
    }

    protected function build()
    {
        foreach ($this->keys as $tag => $code) {
            $this->tags["<{$tag}>"] = $code;
            $this->tags["</{$tag}>"] = $code;
            $this->tags["<\\/{$tag}>"] = $code;
        }
    }

    protected function wrapCodes($codes)
    {
        return "\e[{$codes}m";
    }

    /**
     * 返回标签转化为ansi后的字符串
     *
     * @param string $str
     *
     * @return string
     */
    public function apply(string $str)
    {
        if (Style::isEnableAnsi()) {
            $currentCode = $this->getCurrent($str);
            return $this->start($currentCode) . $this->parse($str, $currentCode) . $this->end();
        } else {
            return $this->applyNoAnsi($str);
        }
    }

    /**
     * 清除所有标签
     *
     * @param $str
     *
     * @return string|null
     */
    public function applyNoAnsi($str)
    {
        return preg_replace($this->regex(), '', $str);
    }

    protected function getCurrent($str)
    {
        if (empty($this->currentRegex)) {
            $this->currentRegex = sprintf('!^<(%s)>.*</\1>$!is', implode('|', array_keys($this->keys)));
        }

        if (preg_match_all($this->currentRegex, $str, $match)) {
            return $this->keys[strtolower($match[1][0])];
        }

        return null;
    }

    /**
     * 返回起始的ansi代码
     *
     * @param null $codes
     *
     * @return string
     */
    protected function start($codes = null)
    {
        $codes = $codes ?: [0];
        $codes = $this->codeStr($codes);

        return $this->wrapCodes($codes);
    }

    /**
     * 返回末尾ansi代码
     *
     * @param null $codes
     *
     * @return string
     */
    protected function end($codes = null)
    {
        if (empty($codes)) {
            $codes = [0];
        } else {
            $codes = to_array($codes);
            array_unshift($codes, 0);
        }

        return $this->wrapCodes($this->codeStr($codes));
    }

    /**
     * 匹配解析全部标签，并替换为ansi代码
     *
     * @param $str
     * @param $currentCode
     *
     * @return mixed
     */
    protected function parse($str, $currentCode)
    {
        $count = preg_match_all($this->regex(), $str, $matches);

        if (!$count || !is_array($matches)) {
            return $str;
        }

        $matches = reset($matches);

        return $this->parseTags($str, $matches, $currentCode);
    }

    /**
     * 解析标签
     *
     * @param $str
     * @param $tags
     * @param $currentCode
     *
     * @return mixed
     */
    protected function parseTags($str, $tags, $currentCode)
    {
        $history = $currentCode !== null ? [$currentCode] : [];

        foreach ($tags as $tag) {
            $str = $this->replaceTag($str, $tag, $history);
        }

        return $str;
    }

    /**
     * 替换标签为ansi代码
     *
     * @param $str
     * @param $tag
     * @param $history
     *
     * @return mixed
     */
    protected function replaceTag($str, $tag, &$history)
    {
        $replace_count = 1;

        if (strpos($tag, '/')) {
            array_pop($history);
            $replace = $this->end($history);
        } else {
            $history[] = $this->value($tag);
            $replace = $this->start($this->value($tag));
        }

        return str_replace($tag, $replace, $str, $replace_count);
    }

    /**
     * 拼接ansi代码
     *
     * @param $codes
     *
     * @return array|string
     */
    protected function codeStr($codes)
    {
        if (!is_array($codes) && strpos((string)$codes, ';')) {
            return $codes;
        }

        $codes = to_array($codes);
        sort($codes);

        return implode(';', $codes);
    }

    /**
     * 返回标签对应的ansi代码
     *
     * @param $key
     *
     * @return mixed|null
     */
    protected function value($key)
    {
        $key = strtolower($key);
        return (array_key_exists($key, $this->tags)) ? $this->tags[$key] : null;
    }

}