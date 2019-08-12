<?php declare(strict_types=1);


namespace Jeekens\Console\Output;


use Jeekens\Console\Exception\Exception;
use Jeekens\Console\Exception\UnknownColorException;
use Jeekens\Console\Exception\UnknownSettingException;

/**
 * Class Modifier
 *
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting
 *
 * @package Jeekens\Console\Output
 */
class Modifier
{

    const SETTING_BOLD = 'bold';  // 加粗
    const SETTING_DIM = 'dim'; // 模糊（不是所有的终端仿真器都支持）
    const SETTING_ITALIC = 'italic'; // 斜体（不是所有的终端仿真器都支持）
    const SETTING_UNDERLINED = 'underlined'; // 下划线
    const SETTING_BLINK = 'blink'; // 闪烁
    const SETTING_REVERSE = 'reverse'; // 颠倒背景与前景
    const SETTING_HIDDEN = 'hidden'; // 隐藏

    const COLOR_DEFAULT = 'default'; // 默认值（前景：通常为绿色，白色或浅灰色。 背景：通常为黑色或蓝色）
    const COLOR_BLACK = 'black'; // 黑色
    const COLOR_RED = 'red'; // 红色（前景：不要使用绿色背景）
    const COLOR_GREEN = 'green'; // 绿色
    const COLOR_YELLOW = 'yellow';  // 黄色
    const COLOR_BLUE = 'blue'; // 蓝色
    const COLOR_PURPLE = 'purple'; // 洋红色/紫色
    const COLOR_CYAN = 'cyan'; // 青色
    const COLOR_LIGHT_GRAY = 'light_gray'; // 浅灰色（背景：不要与白色前景一起使用）
    const COLOR_DARK_GREY = 'dark_grey'; // 深灰色（背景：不要与黑色前景一起使用）
    const COLOR_LIGHT_RED = 'light_red'; // 浅红色
    const COLOR_LIGHT_GREEN = 'light_green'; // 浅绿色（背景：不要与白色前景一起使用）
    const COLOR_LIGHT_YELLOW = 'light_yellow'; // 浅黄色（背景：不要与白色前景一起使用）
    const COLOR_LIGHT_BLUE = 'light_blue'; // 浅蓝色（背景：不要与浅黄色前景一起使用）
    const COLOR_LIGHT_PURPLE = 'light_purple'; // 浅洋红色/粉红色（背景：不要用于浅色前景）
    const COLOR_LIGHT_CYAN = 'light_cyan'; // 浅青色（背景：不要与白色前景一起使用）
    const COLOR_WHITE = 'white'; // 白色（背景：不要用于浅色前景）

    protected static $foreground = [
        self::COLOR_DEFAULT => 39,
        self::COLOR_BLACK => 30,
        self::COLOR_RED => 31,
        self::COLOR_GREEN => 32,
        self::COLOR_YELLOW => 33,
        self::COLOR_BLUE => 34,
        self::COLOR_PURPLE => 35,
        self::COLOR_CYAN => 36,
        self::COLOR_LIGHT_GRAY => 37,
        self::COLOR_DARK_GREY => 90,
        self::COLOR_LIGHT_RED => 91,
        self::COLOR_LIGHT_GREEN => 92,
        self::COLOR_LIGHT_YELLOW => 93,
        self::COLOR_LIGHT_BLUE => 94,
        self::COLOR_LIGHT_PURPLE => 95,
        self::COLOR_LIGHT_CYAN => 96,
        self::COLOR_WHITE => 97,
    ];

    protected static $background = [
        self::COLOR_DEFAULT => 49,
        self::COLOR_BLACK => 40,
        self::COLOR_RED => 41,
        self::COLOR_GREEN => 42,
        self::COLOR_YELLOW => 43,
        self::COLOR_BLUE => 44,
        self::COLOR_PURPLE => 45,
        self::COLOR_CYAN => 46,
        self::COLOR_LIGHT_GRAY => 47,
        self::COLOR_DARK_GREY => 100,
        self::COLOR_LIGHT_RED => 101,
        self::COLOR_LIGHT_GREEN => 102,
        self::COLOR_LIGHT_YELLOW => 103,
        self::COLOR_LIGHT_BLUE => 104,
        self::COLOR_LIGHT_PURPLE => 105,
        self::COLOR_LIGHT_CYAN => 106,
        self::COLOR_WHITE => 107,
    ];

    protected static $setting = [
        self::SETTING_BOLD => 1,
        self::SETTING_DIM => 2,
        self::SETTING_ITALIC => 3,
        self::SETTING_UNDERLINED => 4,
        self::SETTING_BLINK => 5,
        self::SETTING_REVERSE => 7,
        self::SETTING_HIDDEN => 8,
    ];

    /**
     * @var bool
     */
    protected static $isRender = true;

    /**
     * 渲染终端返回的文字样式
     *
     * @param string $string
     * @param string|null $fg
     * @param string|null $bg
     * @param array|null $settings
     *
     * @return string
     *
     * @throws Exception
     * @throws UnknownColorException
     */
    public static function make(string $string, ?string $fg = null, ?string $bg = null, ?array $settings = null): string
    {
        if (empty($string) || ! self::$isRender) {
            return $string;
        }

        $build = self::build($fg, $bg, $settings);

        if (empty($build)) {
            return "\033[".self::$foreground[self::COLOR_DEFAULT]
                .';'.self::$background[self::COLOR_DEFAULT]."m{$string}\033[m";
        }

        $endStr = "\033[m";
        $modifier = strrpos($string, $endStr);
        $format = implode(';', $build);
        $headStr = "\033[{$format}m";
        $startPattern = "#^(?:\033\[[0-9]+?(?:;[0-9]+?)*?m).*?#";
        $hasFormatPattern = "#^.+?\033\[[0-9]+?(?:;[0-9]+?)*?m.*?#";

        if (preg_match($startPattern, $string)) { // 判断字符串开头部分存在样式代码

            $endSize = strlen($endStr);
            $formatString = substr($string, 0, $modifier + $endSize)
                . $headStr . substr($string, $modifier + $endSize) . $endStr;

        } elseif (preg_match($hasFormatPattern, $string)) {  // 判断字符内是否存在样式代码

            $endSize = strlen($endStr);
            $formatString = $headStr . substr($string, 0, $modifier + $endSize)
                . $headStr . substr($string, $modifier + $endSize) . $endStr;
            $headPattern = "#^(\033\[{$format}m.*?)\033\[[0-9]+?(?:;[0-9]+?)*?m.*?#";

            if (preg_match($headPattern, $formatString, $tmp)) {
                $size = strlen($tmp[1]);
                $formatString = substr($formatString, 0, $size)
                    . $endStr . substr($formatString, $size);
            }

        } else {
            $formatString = $headStr.$string.$endStr;
        }

        return $formatString;
    }

    /**
     * @param $fg
     * @param $bg
     * @param $settings
     *
     * @return array
     *
     * @throws Exception
     * @throws UnknownColorException
     */
    private static function build($fg, $bg, $settings): ?array
    {
        $settings = empty($settings) ? [] : $settings;
        $values = [];

        if ($fg !== null && !isset(self::$foreground[$fg])) {
            throw new UnknownColorException(
                sprintf(
                    'Unsupported foreground colors "%s". [%s]',
                    $fg,
                    implode(',', self::getAllForegroundColors())
                )
            );
        }

        if ($bg !== null && !isset(self::$background[$bg])) {
            throw new UnknownColorException(
                sprintf(
                    'Unsupported background colors "%s". [%s]',
                    $fg,
                    implode(',', self::getAllBackgroundColors())
                )
            );
        }

        if ($fg && $bg) {
            $values = [
                self::$foreground[$fg],
                self::$background[$bg]
            ];
        } elseif ($fg) {
            $values = [self::$foreground[$fg]];
        } elseif ($bg) {
            $values = [
                self::$foreground[self::COLOR_DEFAULT],
                self::$background[$bg]
            ];
        }

        foreach ($settings as $setting) {
            if (!is_string($setting)) {
                throw new Exception('Invalid setting.');
            }

            if (!isset(self::$setting[$setting])) {
                throw new UnknownSettingException(
                    sprintf(
                        'Unsupported setting "%s". [%s]',
                        $setting,
                        implode(',', self::getAllSettings())
                    )
                );
            }
            $values[] = self::$setting[$setting];
        }

        return $values;
    }

    /**
     * @return array
     */
    public static function getAllForegroundColors(): array
    {
        return array_keys(self::$foreground);
    }

    /**
     * @return array
     */
    public static function getAllBackgroundColors(): array
    {
        return array_keys(self::$background);
    }

    /**
     * @return array
     */
    public static function getAllSettings(): array
    {
        return array_keys(self::$setting);
    }

    /**
     * 启用样式渲染
     */
    public static function enableRender()
    {
        self::$isRender = true;
    }

    /**
     * 关闭样式渲染
     */
    public static function disableRender()
    {
        self::$isRender = false;
    }

}