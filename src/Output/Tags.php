<?php


namespace Jeekens\Console\Output;


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
     * @var int
     */
    protected $current = 39;


    public function __construct()
    {
        $this->build();
    }

    public function regex()
    {
        return '(<(?:(?:(?:\\\)*\/)*(?:' . implode('|', array_keys($this->keys)) . '))>)is';
    }

    protected function build()
    {
        foreach ($this->keys as $tag => $code) {
            $this->tags["<{$tag}>"]    = $code;
            $this->tags["</{$tag}>"]   = $code;
            $this->tags["<\\/{$tag}>"] = $code;
        }
    }

    protected function wrapCodes($codes)
    {
        return "\e[{$codes}m";
    }

    /**
     * 返回标签转化为ascii后的字符串
     *
     * @param string $str
     *
     * @return string
     */
    public function apply(string $str)
    {
        $this->getCurrent($str);
        return $this->start() . $this->parse($str) . $this->end();
    }

    /**
     * 删除所有标签
     *
     * @param string $str
     *
     * @return string|null
     */
    public function applyNoAscii(string $str)
    {
        return preg_replace($this->regex(), '', $str);
    }

    /**
     * 获取首位的标签
     *
     * @param $str
     */
    protected function getCurrent($str)
    {
        $pattern = sprintf('!^<(%s)>.*</\1>$!is', implode('|', array_keys($this->keys)));
        if (preg_match_all($pattern, $str, $match)) {
            $this->current = $this->keys[strtolower($match[1][0])];
        }
    }

    /**
     * 返回起始的ascii代码
     *
     * @param null $codes
     *
     * @return string
     */
    protected function start($codes = null)
    {
        $codes = $codes ?: $this->currentCode();
        $codes = $this->codeStr($codes);

        return $this->wrapCodes($codes);
    }

    /**
     * 返回末尾ascii代码
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
     * 匹配解析全部标签，并替换为ascii代码
     *
     * @param $str
     *
     * @return mixed
     */
    protected function parse($str)
    {
        $count = preg_match_all($this->regex(), $str, $matches);

        if (!$count || !is_array($matches)) {
            return $str;
        }

        $matches = reset($matches);

        return $this->parseTags($str, $matches);
    }

    /**
     * 解析标签
     *
     * @param $str
     * @param $tags
     *
     * @return mixed
     */
    protected function parseTags($str, $tags)
    {
        $history = ($this->currentCode()) ? [$this->currentCode()] : [];

        foreach ($tags as $tag) {
            $str = $this->replaceTag($str, $tag, $history);
        }

        return $str;
    }

    /**
     * 替换标签为ascii代码
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
     * 拼接ascii代码
     *
     * @param $codes
     *
     * @return array|string
     */
    protected function codeStr($codes)
    {
        if (!is_array($codes) && strpos($codes, ';')) {
            return $codes;
        }

        $codes = to_array($codes);
        sort($codes);

        return implode(';', $codes);
    }

    /**
     * 返回最外层的ascii代码
     *
     * @return array|string
     */
    protected function currentCode()
    {
        return $this->codeStr($this->current);
    }

    /**
     * 返回标签对应的ascii代码
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