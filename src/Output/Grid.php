<?php declare(strict_types=1);


namespace Jeekens\Console\Output;


use InvalidArgumentException;
use Jeekens\Console\Command;

class Grid
{

    protected $rows = [];

    protected $width = 0;

    protected $startDivider = '| ';

    protected $endDivider = ' |';

    protected $headDivider = '=';

    protected $footDivider = '=';

    protected $bodyDivider = '-';

    protected $maxLine = 5;

    protected $widths = [];


    public function __construct($rows = null, int $maxWidth = null)
    {
        if (! empty($rows)) {
            $this->addRow($rows);
        }

        if (empty($maxWidth)) {
            $this->width = Command::getScreenSize(true)[0];
        } else {
            $this->width = $maxWidth;
        }
    }


    public function addRow($rows)
    {

        if (! is_array($rows)) {
            $rows[] = $rows;
        }

        foreach ($rows as $row) {
            if (! ($row instanceof RenderInterface)) throw new InvalidArgumentException(
                sprintf('Row is must be instance "%s"', RenderInterface::class)
            );
            $this->rows[] = $row;
            $this->widths[] = $row->getWidths();
        }
    }

    public function render()
    {
        $this->scanRenderOptions();
    }

    protected function scanRenderOptions()
    {

    }

}