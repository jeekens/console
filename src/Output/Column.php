<?php declare(strict_types=1);


namespace Jeekens\Console\Output;


use Jeekens\Basics\Str;

class Column implements RenderInterface
{

    /**
     * @var int
     */
    protected $maxLine = 5;

    /**
     * @var string
     */
    protected $string = '';


    public function __construct(?string $string = null)
    {
        $this->setString($string);
    }

    public function setString(?string $string)
    {

        if ($string === null) {
            $string = 'NULL';
        }

        $this->string = trim($string);
    }

    public function setMaxLine(int $line)
    {
        $this->maxLine = $line;
        return $line;
    }

    public function render(int ... $widths): string
    {
        $width = $widths[0] ?? 0;
        if ($width > 0) {
            if ($this->maxLine > 0) {
                $arr = str_split($this->string, $width);
                if (count($arr) > $this->maxLine) {
                    $arr = array_slice($arr, $this->maxLine);
                    end($arr);
                    $endKey = key($arr);
                    $arr[$endKey] = Str::limit($arr[$endKey], 'UTF-8', $width - mb_strwidth('...'));
                }
                return implode("\n", $arr);
            } else {
                return Str::limit($this->string, 'UTF-8', $width - mb_strwidth('...'));
            }
        }

        return $this->string;
    }

    public function getWidth(): int
    {
        return mb_strwidth(clear_style(Style::tags()
            ->applyNoAnsi($this->string)), 'UTF-8');
    }

    public function getWidths(): array
    {
        return [
            $this->getwidth()
        ];
    }

}