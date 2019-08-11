<?php declare(strict_types=1);

use Jeekens\Console\Output\Modifier;

if (function_exists('modifier')) {
    /**
     * 命令行文字修饰方法
     *
     * @param string $string
     * @param string|null $fg
     * @param string|null $bg
     * @param array|null $settings
     *
     * @return string
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    function modifier(string $string, ?string $fg = null, ?string $bg = null, ?array $settings = null): string
    {
        return Modifier::make($string, $fg, $bg, $settings);
    }
}
