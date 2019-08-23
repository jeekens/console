<?php declare(strict_types=1);
/**
 * This file is part of the PHPLucidFrame library.
 * The class makes you easy to build console style tables
 *
 * @package     PHPLucidFrame\Console
 * @since       PHPLucidFrame v 1.12.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        https://github.com/phplucidframe/console-table
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace Jeekens\Console\Output;


use Jeekens\Console\Command;

class Table
{
    const HEADER_INDEX = -1;
    const HR = 'HR';

    /**
     * @var array Array of table data
     */
    protected $data = [];

    /**
     * @var bool Border shown or not
     */
    protected $border = true;

    /**
     * @var bool All borders shown or not
     */
    protected $allBorders = false;

    /**
     * @var int Table padding
     */
    protected $padding = 1;

    /**
     * @var int Table left margin
     */
    protected $indent = 0;

    /**
     * @var int
     */
    private $rowIndex = -1;

    /**
     * @var array
     */
    private $columnWidths = [];

    /**
     * @var int
     */
    private $tableIndex = 0;

    /**
     * @var int[]
     */
    private $maxRowWidth = [];

    /**
     * Adds a column to the table header
     *
     * @param mixed  Header cell content
     *
     * @return $this
     */
    public function addHeader($content = '')
    {
        $this->data[$this->tableIndex][self::HEADER_INDEX][] = $content;

        return $this;
    }

    /**
     * Set headers for the columns in one-line
     *
     * @param array  Array of header cell content
     *
     * @return $this
     */
    public function setHeaders(array $content)
    {
        $this->data[$this->tableIndex][self::HEADER_INDEX] = $content;

        return $this;
    }

    /**
     * Get the row of header
     */
    public function getHeaders(int $tableIndex = null)
    {
        $tableIndex = $tableIndex ?? $this->tableIndex;
        return isset($this->data[$tableIndex][self::HEADER_INDEX]) ?? null;
    }

    /**
     * @param array|null $data
     * @param bool $br
     *
     * @return $this
     */
    public function addRow(?array $data = null, bool $br = false)
    {
        $this->rowIndex++;

        if (empty($data) && $br) {
            $this->data[$this->tableIndex][$this->rowIndex][] = null;
            $this->rowIndex++;
        } elseif (! empty($data)) {
            foreach ($data as $col => $content) {
                $this->data[$this->tableIndex][$this->rowIndex][$col] = $content;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addTable()
    {
        $this->tableIndex++;
        $this->rowIndex = -1;
        return $this;
    }

    /**
     * Adds a column to the table
     *
     * @param mixed $content The data of the column
     * @param integer $col The column index to populate
     * @param integer $row If starting row is not zero, specify it here
     *
     * @return $this
     */
    public function addColumn($content, $col = null, $row = null)
    {
        $row = $row === null ? $this->rowIndex : $row;
        if ($col === null) {
            $col = isset($this->data[$this->tableIndex][$row]) ? count($this->data[$this->tableIndex][$row]) : 0;
        }

        $this->data[$this->tableIndex][$row][$col] = $content;

        return $this;
    }

    /**
     * Show table border
     *
     * @return $this
     */
    public function showBorder()
    {
        $this->border = true;

        return $this;
    }

    /**
     * Hide table border
     *
     * @return $this
     */
    public function hideBorder()
    {
        $this->border = false;

        return $this;
    }

    /**
     * Show all table borders
     *
     * @return $this
     */
    public function showAllBorders()
    {
        $this->showBorder();
        $this->allBorders = true;

        return $this;
    }

    /**
     * Set padding for each cell
     *
     * @param integer $value The integer value, defaults to 1
     *
     * @return $this
     */
    public function setPadding($value = 1)
    {
        $this->padding = $value;

        return $this;
    }

    /**
     * Set left indentation for the table
     *
     * @param integer $value The integer value, defaults to 1
     *
     * @return $this
     */
    public function setIndent($value = 0)
    {
        $this->indent = $value;

        return $this;
    }

    /**
     * Add horizontal border line
     *
     * @return $this
     */
    public function addBorderLine()
    {
        $this->rowIndex++;
        $this->data[$this->tableIndex][$this->rowIndex] = self::HR;

        return $this;
    }

    /**
     * @throws \Jeekens\Console\Exception\Exception
     *
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function display()
    {
        Command::write($this->getTable());
    }

    /**
     * Get the printable table content
     * @return string
     */
    public function getTable()
    {
        $this->calculateColumnWidth();
        $output = '';
        $keys = end($this->data);
        $last = key($keys);

        foreach ($this->data as $tableIndex => $table) {

            $output .= $this->border ? $this->getBorderLine($tableIndex) : '';

            foreach ($table as $y => $row) {
                if ($row === self::HR) {
                    if (!$this->allBorders) {
                        $output .= $this->getBorderLine($tableIndex);
                        unset($this->data[$tableIndex][$y]);
                    }

                    continue;
                }

                foreach ($row as $x => $cell) {
                    $output .= $this->getCellOutput($tableIndex, $x, $row);
                }
                $output .= PHP_EOL;

                if ($y === self::HEADER_INDEX) {
                    $output .= $this->getBorderLine($tableIndex);
                } else {
                    if ($this->allBorders) {
                        $output .= $this->getBorderLine($tableIndex);
                    }
                }
            }
        }

        if (!$this->allBorders) {
            $output .= $this->border ? $this->getBorderLine() : '';
        }

        return $output;
    }

    /**
     * Get the printable border line
     *
     * @param int $table
     *
     * @return string
     */
    private function getBorderLine(?int $table = null)
    {
        $output = '';

        if ($this->border) {
            $table = $table ?? $this->tableIndex;

            if (isset($this->data[$table][0])) {
                $columnCount = count($this->data[$table][0]);
            } elseif (isset($this->data[$table][self::HEADER_INDEX])) {
                $columnCount = count($this->data[$table][self::HEADER_INDEX]);
            } else {
                return $output;
            }

            for ($col = 0; $col < $columnCount; $col++) {
                $output .= $this->getCellOutput($table, $col);
            }

            $output .= '+';
            $output .= PHP_EOL;
        }

        return $output;
    }

    /**
     * Get the printable cell content
     *
     * @param integer $index The column index
     * @param array $row The table row
     * @return string
     */
    private function getCellOutput($table, $index, $row = null)
    {
        $cell = $row ? $row[$index] : '-';
        $width = $this->columnWidths[$table][$index];
        $padding = str_repeat($row ? ' ' : '-', $this->padding);
        $output = '';

        if ($index === 0) {
            $output .= str_repeat(' ', $this->indent);
        }

        if ($this->border) {
            $output .= $row ? '|' : '+';
        }

        $output .= $padding; # left padding
        $cell = preg_replace('/[\n\r]+/', ' ', $cell); # remove line breaks
        $content = preg_replace('#\x1b[[][^A-Za-z]*[A-Za-z]#', '', $cell);
        $delta = mb_strlen($cell, 'UTF-8') - mb_strlen($content, 'UTF-8');
        $output .= $this->strPadUnicode($cell, $width + $delta, $row ? ' ' : '-'); # cell content
        $output .= $padding; # right padding
        if ($row && $index == count($row) - 1 && $this->border) {
            $output .= $row ? '|' : '+';
        }

        return $output;
    }

    /**
     * Calculate maximum width of each column
     *
     * @return array
     */
    private function calculateColumnWidth()
    {
        foreach ($this->data as $tableIndex => $table) {
            foreach ($table as $y => $row) {
                $tmp = 0;
                if (is_array($row)) {
                    foreach ($row as $x => $col) {
                        $content = clear_style(Style::tags()
                            ->applyNoAnsi(preg_replace('#\x1b[[][^A-Za-z]*[A-Za-z]#', '', $col)));
                        if (!isset($this->columnWidths[$tableIndex][$x])) {
                            $this->columnWidths[$tableIndex][$x] = mb_strlen($content, 'UTF-8');
                            $tmp += $this->columnWidths[$tableIndex][$x];
                        } else {
                            if (mb_strlen($content, 'UTF-8') > $this->columnWidths[$tableIndex][$x]) {
                                $this->columnWidths[$tableIndex][$x] = mb_strlen($content, 'UTF-8');
                                $tmp += $this->columnWidths[$tableIndex][$x];
                            }
                        }
                    }
                }
                if (empty($this->maxRowWidth[$tableIndex]) || $this->maxRowWidth[$tableIndex] < $tmp) {
                    $this->maxRowWidth[$tableIndex] = $tmp;
                }
            }
        }

        return $this->columnWidths;
    }

    /**
     * Multibyte version of str_pad() function
     *
     * @param $str
     * @param $padLength
     * @param string $padString
     * @param int $dir
     *
     * @return string|null
     */
    private function strPadUnicode($str, $padLength, $padString = ' ', $dir = STR_PAD_RIGHT)
    {
        $strLen = mb_strlen($str, 'UTF-8');
        $padStrLen = mb_strlen($padString, 'UTF-8');

        if (!$strLen && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $strLen = 1;
        }

        if (!$padLength || !$padStrLen || $padLength <= $strLen) {
            return $str;
        }

        $result = null;
        $repeat = ceil($strLen - $padStrLen + $padLength);
        if ($dir == STR_PAD_RIGHT) {
            $result = $str . str_repeat($padString, (int)$repeat);
            $result = mb_substr($result, 0, $padLength, 'UTF-8');
        } elseif ($dir == STR_PAD_LEFT) {
            $result = str_repeat($padString, $repeat) . $str;
            $result = mb_substr($result, -$padLength, null, 'UTF-8');
        } elseif ($dir == STR_PAD_BOTH) {
            $length = ($padLength - $strLen) / 2;
            $repeat = ceil($length / $padStrLen);
            $result = mb_substr(str_repeat($padString, (int)$repeat), 0, floor($length), 'UTF-8')
                . $str
                . mb_substr(str_repeat($padString, (int)$repeat), 0, ceil($length), 'UTF-8');
        }

        return $result;
    }
}
