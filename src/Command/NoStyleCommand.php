<?php declare(strict_types=1);


namespace Jeekens\Console\Command;


use function array_values;
use function is_string;
use Jeekens\Console\Command;
use Jeekens\Console\CommandInterface;
use Jeekens\Console\Output\Style;
use function sprintf;

/**
 * Class NoStyleCommand
 * @package Jeekens\Console\Command
 */
class NoStyleCommand implements CommandInterface
{

    public $name = 'noStyle';

    public $options = [
        '--no-style' => 'If true, no ansi string output.',
    ];

    public $usage = 'Used to no ansi string output.';

    public $describe = 'No ansi string output.';

    public $arguments = [
        'Command name.',
    ];

    /**
     * @return bool
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function handle()
    {
        Style::disableAnsi();
        $param = Command::getParam();
        $commandName = $param[1] ?? null;

        if (! is_string($commandName) || empty($commandName)){
            Command::error('Command name is must be a string.', true);
        }

        $param = array_values($param);
        unset($param[0]);
        Command::setParam($param);

        return Command::execute($commandName);
    }

    /**
     * @return string
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function getExample()
    {
        return sprintf('Execute: php %s noStyle "commandName"', Command::getScript());
    }

}