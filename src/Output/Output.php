<?php declare(strict_types=1);


namespace Jeekens\Console\Output;

/**
 * Class Output
 *
 * @package Jeekens\Console\Output
 */
class Output
{

    /**
     * @var bool|resource
     */
    protected $outputStream = STDOUT;

    /**
     * @var bool|resource
     */
    protected $errorStream = STDERR;

    /**
     * @var bool
     */
    protected $isEnableBuff = false;

    /**
     * @var int
     */
    protected $bufferMaxSize = 1024;

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var Tags
     */
    protected $tags;


    /**
     * Output constructor.
     *
     * @param null $outputStream
     * @param null $errorStream
     */
    public function __construct($outputStream = null, $errorStream = null)
    {
        if (! empty($outputStream)) {
            $this->outputStream = $outputStream;
        }

        if (! empty($errorStream)) {
            $this->errorStream = $errorStream;
        }

        $this->tags = new Tags();
    }

    /**
     * 输出数据
     *
     * @param mixed $messages
     * @param bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @param int|boolean $quit 如果为int则输出后退出
     * @param bool $isErr
     *
     * @return int
     */
    public function write($messages, $nl = true, $quit = false, bool $isErr = false): int
    {
        if (is_array($messages)) {
            $messages = implode($nl ? PHP_EOL : '', $messages);
        }

        $messages = $this->tags->apply($messages);

        if (! Tags::isEnableAnsi()) {
            $messages = clear_style($messages);
        }

        $messages .= $nl ? PHP_EOL : '';

        $this->stdWrite($messages, $isErr);

        if ($quit === false) {
            return 0;
        }

        // if will quit.
        if ($quit !== false) {
            $code = true === $quit ? 0 : (int)$quit;
            exit($code);
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function isEnableBuff(): bool
    {
        return $this->isEnableBuff;
    }

    /**
     * 清空缓冲内容
     *
     */
    public function clearBuffer()
    {
        if ($this->isEnableBuff()) {
            $this->buffer = '';
        }
    }

    /**
     * 开启输出缓冲区
     */
    public function enableBuffer()
    {
        if (! $this->isEnableBuff()) {
            $this->isEnableBuff = true;
        }
    }

    /**
     * 关闭输出缓冲区
     *
     * @param bool $isFlush
     */
    public function disableBuffer(bool $isFlush = true)
    {
        if ($this->isEnableBuff()) {

            if ($isFlush) {
                $this->flush(true);
            }

            $this->buffer = '';
            $this->isEnableBuff = false;
        }
    }

    /**
     * 设置缓冲区最大限制
     *
     * @param int $size
     */
    public function setBufferMaxSize(int $size)
    {
        if ($size > 0) {
            $this->bufferMaxSize = $size;
        }
    }

    /**
     * 获取缓冲区内容
     *
     * @return string|null
     */
    public function getBufferContent()
    {
        if ($this->isEnableBuff()) {
            return $this->buffer;
        } else {
            return null;
        }
    }

    /**
     * 获取缓冲区内容大小
     *
     * @return int
     */
    public function getBufferSize(): int
    {
        return strlen($this->buffer);
    }

    /**
     * 强制刷出缓冲区内容
     */
    public function forceFlushBuffer()
    {
        $this->flush(true);
    }

    /**
     * 刷出缓冲区
     *
     * @param bool $force
     */
    protected function flush(bool $force = false)
    {
        if (! $this->isEnableBuff() && $this->getBufferSize() == 0) return;

        if ($force || $this->getBufferSize() >= $this->bufferMaxSize) {
            fwrite($this->outputStream, $this->buffer)
            && $this->buffer = '';
        }
    }

    /**
     * 标准输出
     *
     * @param $messages
     * @param bool $isErr
     *
     */
    protected function stdWrite($messages, bool $isErr = false)
    {

        if ($this->isEnableBuff()) {
            $this->buffer .= $messages;

            if ($isErr) {
                fwrite($this->errorStream, $this->buffer)
                && $this->buffer = '';
            } elseif ($this->getBufferSize() >= $this->bufferMaxSize) {
                fwrite($this->outputStream, $this->buffer)
                && $this->buffer = '';
            }

        } elseif ($isErr) {
            fwrite($this->errorStream, $messages);
        } else {
            fwrite($this->outputStream, $messages);
        }
    }

}