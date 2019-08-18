<?php declare(strict_types=1);


namespace Jeekens\Console\Command;


use Jeekens\Console\Command;
use Jeekens\Console\CommandInterface;
use function sprintf;

class HelpCommand implements CommandInterface
{

    public $name = 'help';

    public $options = [
        '-n, --name' => 'command name',
        '-a, --arguments' => 'If true, show command arguments can also be an index of arguments.',
        '-e, --example' => 'If true, show command example.',
        '-u, --usage' => 'If true, show command usage.',
        '-i, --info' => 'If true, show command describe. ',
        '-o, --options' => 'If true, show command options, can be an option name.',
    ];

    public $usage = 'Used to output help content.';

    public $describe = 'Help command.';

    public $arguments = [
        'Command name.',
        'Index of arguments.',
        'Option name'
    ];

    /**
     * @return string
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function example()
    {
        return sprintf('Execute: php %s help "commandName"', Command::getScript());
    }


    public function handle()
    {

    }

}