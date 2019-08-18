<?php


namespace Jeekens\Console\Output;


use Jeekens\Basics\Arr;

class Table
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @var int
     */
    protected $nowIndex = 0;

    /**
     * Table constructor.
     *
     * @param $data
     *
     * @throws \Exception
     */
    public function __construct($data = [])
    {
        $this->setData($data);
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public function setData(array $data)
    {
        if (! empty($data) ) {

            if (! Arr::isAssoc($data)) {
                $this->data = $data;
                end($data);
                $this->nowIndex = key($data) + 1;
            }

            throw new \Exception('Data is must be a index array.');
        }
    }

    /**
     * 添加新的行
     *
     * @return $this
     */
    public function addRow()
    {
        $this->nowIndex++;
        return $this;
    }

    /**
     * 添加新的一列数据
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function addColumn(string $name, string $value)
    {
        $this->data[$this->nowIndex][$name] = $value;
        return $this;
    }

}