<?php declare(strict_types=1);


namespace Jeekens\Console\Output;

use Jeekens\Basics\Os;

/**
 * Class Style
 *
 * @package Jeekens\Console\Output
 */
class Style
{

    /**
     * @var Tags
     */
    protected static $tags;

    /**
     * @var bool
     */
    protected static $isAnsi = true;

    /**
     * 开启ansi
     */
    public static function enableAnsi()
    {
        self::$isAnsi = true;
    }

    /**
     * 关闭ansi
     */
    public static function disableAnsi()
    {
        self::$isAnsi = false;
    }

    /**
     * 判断当前是否支持ansi
     *
     * @return bool
     */
    public static function isEnableAnsi()
    {
        return self::$isAnsi && Os::systemHasAnsiSupport(Os::isWin());
    }

    /**
     * @return Tags
     */
    public static function tags()
    {
        if (empty(self::$tags)) {
            self::$tags = new Tags();
        }
        return self::$tags;
    }

}