<?php

namespace Jeekens\Console;


use Throwable;

/**
 * Interface ExceptionHandle
 *
 * @package Jeekens\Console
 */
interface ExceptionHandle
{

    public function handle(Throwable $e): ?Throwable;

}