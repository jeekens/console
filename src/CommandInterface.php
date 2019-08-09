<?php


namespace Jeekens\console;



use Jeekens\console\Output\StyleInterface;

interface CommandInterface
{


    /**
     * 获取执行的脚本
     *
     * @return string
     */
    public function getScript(): string;

    /**
     * 获取执行的命令
     *
     * @return string
     */
    public function getCommand(): string;

    /**
     * 询问用户并返回用户的输入值
     *
     * @param string|StyleInterface $output
     *
     * @return mixed
     */
    public function ask($output);

    /**
     * 请求确认
     *
     * @param string|StyleInterface $output
     *
     * @return mixed
     */
    public function confirm($output);

    /**
     * 提供给用户一个选择
     *
     * @param string|StyleInterface $output
     * @param array $choices
     * @param null $defaultIndex
     *
     * @return mixed
     */
    public function choice($output, $choices, $defaultIndex = null);

    /**
     * 请求确认输入
     *
     * @param string|StyleInterface $outputOne
     * @param string|StyleInterface $outputTwo
     *
     * @return mixed
     */
    public function confirmInput($outputOne, $outputTwo = null);

    /**
     * 提供给用户一个单选项
     *
     * @param string|StyleInterface $output
     * @param $options
     * @param $defaultIndex
     *
     * @return mixed
     */
    public function checkbox($output, $options, $defaultIndex = null);

    /**
     * 提供给用户一个多选项
     *
     * @param $output
     * @param $options
     *
     * @return mixed
     */
    public function multiSelect($output, $options);

}