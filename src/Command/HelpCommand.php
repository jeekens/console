<?php declare(strict_types=1);


namespace Jeekens\Console\Command;


use Jeekens\Console\Command;
use Jeekens\Console\CommandInterface;
use function sprintf;

/**
 * Class HelpCommand
 * @package Jeekens\Console\Command
 */
class HelpCommand implements CommandInterface
{

    public $name = 'help';

    public $options = [
        '-n, --name' => 'command name',
    ];

    public $usage = 'Used to output help content.';

    public $describe = 'Help command.';

    public $arguments = [
        'Command name.',
    ];

    /**
     * @return string
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function getExample()
    {
        return sprintf('Execute: php %s help "commandName"', Command::getScript());
    }


    public function handle()
    {

    }

}