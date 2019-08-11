<?php declare(strict_types=1);


namespace Jeekens\Console\Command;


use Jeekens\Console\Command;
use Jeekens\Console\Output\Table;
use Jeekens\Console\Output\Modifier;
use Jeekens\Console\CommandInterface;

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
     * @throws \Jeekens\Console\Exception\CommandNameParseException
     */
    public function example()
    {
        return sprintf('Execute: php %s help "commandName"', Command::getScript());
    }


    public function handle()
    {
        $info = Command::getCommandInfos()['help'];

        if (! empty($info['command']->describe)) {
            echo \modifier('Command Describe: ', Modifier::COLOR_GREEN),PHP_EOL;
            echo $info['command']->describe,PHP_EOL,PHP_EOL;
        }

        if (! empty($info['command']->usage)) {
            echo \modifier('Usage: ', Modifier::COLOR_GREEN),PHP_EOL;
            echo $info['command']->usage,PHP_EOL,PHP_EOL;
        }

        if (! empty($info['command']->arguments)) {
            $table = new Table();
            echo \modifier('Arguments: ', Modifier::COLOR_GREEN),PHP_EOL;
            foreach ($info['command']->arguments as $key => $val) {
                $table
                    ->addRow([
                        \modifier('Arguments'.((string)($key+1)), Modifier::COLOR_RED),
                        $val
                    ]);
            }
            $table->hideBorder()->display();
        }

        if (! empty($info['command']->options)) {
            $table = new Table();
            echo \modifier('Options: ', Modifier::COLOR_GREEN),PHP_EOL;
            foreach ($info['command']->options as $key => $val) {
                $table
                    ->addRow([
                        \modifier($key, Modifier::COLOR_YELLOW),
                        $val
                    ]);
            }
            $table->hideBorder()->display();
        }

    }

}