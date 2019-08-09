<?php declare(strict_types=1);


namespace Jeekens\Console\Input;

/**
 * Class ArgsParse
 *
 * @package Jeekens\Console\Input
 */
class ArgsParse
{

    private const TRUE_WORDS = ['on', 'yes', 'true'];

    private const FALSE_WORDS = ['off', 'no', 'false'];

    /**
     * @param array $args
     *
     * @return array
     */
    public static function flag(array $args)
    {
        $params = $shortOpts = $longOpts = $arrayOpts = [];

        while (false !== ($p = current($args))) {
            next($args);

            // 判断是否为一个选项
            if ($p{0} === '-') {
                $value = true;
                $option = substr($p, 1); // 移除开头的-符号
                $isLong = false;
                // 判断是否为一个长选项 --opts
                if (strpos($option, '-') === 0) {
                    $option = substr($option, 1); // 再次移除开头的-符号
                    $isLong = true;
                    // 判断是否存在等号--opts=value
                    if (strpos($option, '=') !== false) {
                        [$option, $value] = explode('=', $option, 2);
                    }

                } elseif (isset($option{1}) && $option{1} === '=') { // 判断当前是否为等号开头，如果是则表示为-o=value形式
                    [$option, $value] = explode('=', $option, 2);
                } elseif (strlen($option) > 1) { // 判断当前opt是否长度已经大于1，如果是则表示用户忘记输入=或空格
                   $tmp = $option;
                   $option = $tmp{0};
                   $value = substr($tmp, 1);
                }

                // 返回当前指针值
                $next = current($args);

                // 判断下一个元素是否为输入值
                if ($value === true && $next !== false && $next != null
                    && $next{0} !== '-' && false === strpos($next, '=')) {

                    $value = $next;
                    next($args);

                } elseif (!$isLong && $value === true) { // 如果是非长选项则表示为批量短选项赋值true
                    foreach (str_split($option) as $char) {
                        $shortOpts[$char] = true;
                        $arrayOpts[$char][] = true;
                    }
                    continue;
                }

                $value   = self::valueFilter($value);

                if ($isLong) {
                    $longOpts[$option] = $value;
                } else {
                    $shortOpts[$option] = $value;
                }

                $arrayOpts[$option][] = $value;

                continue;
            }

            if (strpos($p, '=') !== false) {
                [$name, $value] = explode('=', $p, 2);
                $params[$name] = self::valueFilter($value);
            } else {
                $params[] = $p;
            }
        }

        return [$params, $shortOpts, $longOpts, $arrayOpts];
    }

    /**
     * @param $val
     *
     * @return bool|string
     */
    protected static function valueFilter($val)
    {
        if (is_bool($val) || is_numeric($val)) {
            return $val;
        }

        $val = strtolower($val);

        if (false !== array_search($val, self::TRUE_WORDS)) {
            return true;
        }

        if (false !== array_search($val, self::FALSE_WORDS)) {
            return false;
        }

        return $val;
    }

}