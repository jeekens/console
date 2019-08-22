<?php declare(strict_types=1);

namespace Jeekens\Console\Output\ProgressBar;

/**
 * Class Registry
 *
 * @package Jeekens\Console\Output\ProgressBar
 */
class Registry
{
    /**
     * Registry
     */
    public $registry = array();

    /**
     * Sets the value for a key
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setValue($key, $value)
    {
      $this->registry[$key] = $value;
    }

    /**
     * Returns the value associated to a key
     * 
     * @param string $key
     * @return mixed
     * @throws \RunTimeException
     */
    public function getValue($key)
    {
      if (!isset($this->registry[$key]))
        throw new \RunTimeException('Invalid offset requested');

      return $this->registry[$key];
    }
}
