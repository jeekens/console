<?php declare(strict_types=1);

if (! function_exists('clear_style')) {
    /**
     * 清除命令行输出字符的样式
     *
     * @param string $string
     *
     * @return string
     */
    function clear_style(string $string): string
    {
        return preg_replace([
            "(\033|\e|\x1B)[[0-9]+?(?:;[0-9]+?)*?m"
        ], '', $string);
    }
}