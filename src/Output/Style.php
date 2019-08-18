<?php declare(strict_types=1);


namespace Jeekens\Console\Output;

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