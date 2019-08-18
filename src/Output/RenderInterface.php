<?php


namespace Jeekens\Console\Output;


interface RenderInterface
{

    public function render(int ... $widths): string;

    public function getWidth(): int;

    public function setMaxLine(int $line);

    public function getWidths(): array;

}