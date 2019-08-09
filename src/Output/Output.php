<?php


namespace Jeekens\Console\Output;


interface Output
{

    /**
     * Write a message to standard output stream.
     *
     * @param mixed       $messages
     * @param bool        $nl true 会添加换行符 false 原样输出，不添加换行符
     * @param int|boolean $quit 如果为int则输出后退出
     * @param array       $opts
     *
     * @return int
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int;

}