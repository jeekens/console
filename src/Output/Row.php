<?php declare(strict_types=1);


namespace Jeekens\Console\Output;


use InvalidArgumentException;

class Row implements RenderInterface
{

    /**
     * @var Column[]
     */
    protected $columns = [];

    /**
     * @var string
     */
    protected $columnDivider = ' | ';

    /**
     * @var int
     */
    protected $maxLine = 5;

    /**
     * @var int[]
     */
    protected $columnsWidth = [];

    /**
     * @var int
     */
    protected $dividerLength = 3;


    public function __construct($columns = null)
    {
        if (!empty($columns)) {
            $this->addColumn($columns);
        }

        $this->dividerLength = mb_strwidth(clear_style(Style::tags()->applyNoAnsi($this->columnDivider)), 'UTF-8');
    }

    /**
     * 设置每列分隔符
     *
     * @param string $divider
     *
     * @return $this
     */
    public function setDivider(string $divider)
    {
        $this->columnDivider = $divider;
        $this->dividerLength = mb_strwidth(clear_style(Style::tags()->applyNoAnsi($this->columnDivider)), 'UTF-8');
        return $this;
    }

    /**
     * 添加个列
     *
     * @param $columns
     */
    public function addColumn($columns)
    {
        if (!is_array($columns)) {
            $columns[] = $columns;
        }

        foreach ($columns as $column) {
            if (!($column instanceof Column)) throw new InvalidArgumentException(
                sprintf('Column is must be instance "%s"', Column::class)
            );
            $this->columns[] = $column;
            $column->setMaxLine($this->maxLine);
            $this->columnsWidth[] = $column->getWidth();
        }
    }

    public function setMaxLine(int $line)
    {
        $this->maxLine = $line;
        return $line;
    }

    /**
     * 返回渲染后的列字符串
     *
     * @param int[] $widths
     *
     * @return string
     */
    public function render(int ... $widths): string
    {
        if (empty($widths)) {
            $widths = $this->columnsWidth;
        }

        return $this->rendered($widths);
    }


    protected function rendered(int ... $widths): string
    {
        if (empty($widths)) {
            return '';
        }

        $string = '';
        $i = 0;
        $lines = [];
        $maxLine = 1;
        $columnCount = count($widths);
        foreach ($this->columns as $column) {
            $tmp = $column->render($widths[$i]);
            if (strpos($tmp, "\n")) {
                $arr = explode("\n", $tmp);
                $lines[] = $arr;
                $line = count($arr);
                $maxLine = $line > $maxLine ? $line : $maxLine;
            } else {
                $lines[] = $tmp;
            }
            $i++;
        }

        if ($maxLine > 1) {
            foreach ($lines as $key => $line) {
                $tmpC = count($line);
                if ($maxLine > $tmpC) {
                    $average = ($maxLine - $tmpC) / 2;
                    $temArr = array_pad($line, -(floor($average) + $tmpC), '');
                    $lines[$key] = array_pad($temArr, count($temArr) + ceil($average), '');
                }
            }
        }

        $n = 0;
        while ($n < $maxLine) {
            $j = 0;
            while ($j < $columnCount) {
                $string .= str_pad($lines[$n][$j], $widths[$j]) . $this->columnDivider;
            }
            $string = rtrim($string, $this->columnDivider) . "\n";
        }

        return $string;
    }

    /**
     * 返回列宽
     *
     * @return int
     */
    public function getWidth(): int
    {
        return array_sum($this->columnsWidth) + (count($this->columns) - 1) * $this->dividerLength;
    }

    /**
     * @return array
     */
    public function getWidths(): array
    {
        return $this->columnsWidth;
    }

}