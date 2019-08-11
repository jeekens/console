<?php declare(strict_types=1);


namespace Jeekens\Console\Input;

/**
 * Class Input
 *
 * @package Jeekens\Console\Input
 */
class Input
{

    /**
     * @var bool|resource
     */
    protected $inputStream = STDIN;

    /**
     * @var null|array
     */
    protected $args = [];

    /**
     * @var null|array
     */
    protected $shortOpts = [];

    /**
     * @var null|array
     */
    protected $longOpts = [];

    /**
     * @var null|array
     */
    protected $arrayOpts= [];

    /**
     * @var string|null
     */
    protected $script = null;

    /**
     * @var null|string
     */
    protected $pwd = null;

    /**
     * @var null|string
     */
    protected $commandRaw = null;

    /**
     * Input constructor.
     *
     * @param array|null $args
     * @param bool $parsing
     */
    public function __construct(array $args = null, bool $parsing = true)
    {
        if (null === $args) {
            $args = (array)$_SERVER['argv'];
        }

        $this->pwd        = $this->getPwd();
        $this->script = array_shift($args);
        $this->commandRaw = implode(' ', $args);

        if ($parsing) {
            [$this->args, $this->shortOpts, $this->longOpts, $this->arrayOpts] = ArgsParse::flag($args);
        } else {
            $this->args = $args;
        }
    }

    /**
     * @return string|null
     */
    public function getPwd()
    {
        if ($this->pwd === null) {
            $this->pwd = getcwd();
        }

        return $this->pwd;
    }

    /**
     * @return string|null
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     */
    public function getShortOpts($key = null, $default = null)
    {
        if ($key == null) {
            return $this->shortOpts;
        }

        return $this->shortOpts[$key] ?? $default;
    }

    /**
     * @return array|null
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     */
    public function getLongOpts($key = null, $default = null)
    {
        if ($key == null) {
            return $this->longOpts;
        }

        return $this->longOpts[$key] ?? $default;
    }

    /**
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     */
    public function getArrayOpts($key = null, $default = null)
    {
        if ($key == null) {
            return $this->arrayOpts;
        }

        return $this->arrayOpts[$key] ?? $default;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasShortOpts($key)
    {
        return isset($this->shortOpts[$key]);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasLongOpts($key)
    {
        return isset($this->longOpts[$key]);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasArrayOpts($key)
    {
        return isset($this->arrayOpts[$key]);
    }

    /**
     * @param array $keys
     *
     * @return bool
     */
    public function hasOneOpts(array $keys)
    {
        foreach ($keys as $key) {
            if (isset($this->arrayOpts[$key])) {
                return true;
            }
        }

        return false;
    }

}