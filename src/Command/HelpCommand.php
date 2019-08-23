<?php declare(strict_types=1);


namespace Jeekens\Console\Command;


use Jeekens\Console\Command;
use Jeekens\Console\CommandInterface;
use Jeekens\Console\Output\Table;
use function sprintf;

/**
 * Class HelpCommand
 * @package Jeekens\Console\Command
 */
class HelpCommand implements CommandInterface
{

    public $name = 'help';

    public $usage = 'Used to output command help content.';

    public $describe = 'Help command.';

    public $arguments = [
        'Command name.',
    ];

    public $bindOpts = [
        '-h, --help'
    ];

    /**
     * @return string
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function getExample()
    {
        return sprintf('php %s help "commandName"', Command::getScript());
    }


    /**
     * @return bool
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    public function handle()
    {
        $commandName = Command::getFullCommandName();

        if ($commandName === 'help' || empty($commandName)) {
            $commandName = Command::getParam(1) ?? Command::getParam('command');
        }

        Command::clear();
        Command::line();

        if (empty($commandName)) {
            $this->noCommand();
        } else {
            $this->command($commandName);
        }

        return false;
    }

    /**
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    protected function noCommand()
    {
        $commandInfos = Command::getCommandInfos();
        Command::line('<yellow>Command List</yellow>: ');
        Command::line();
        $table = new Table();
        $table->addHeader('<light_red>Command Name</light_red>')
            ->addHeader('<light_red>Describe</light_red>')
            ->addHeader('<light_red>Usage</light_red>');

        foreach ($commandInfos as $commandName => $item) {
            $table->addRow()
                ->addColumn(sprintf('<cyan>%s</cyan>', $commandName))
                ->addColumn($item['describe'])
                ->addColumn($item['usage']);
        }

        $table->showAllBorders()->display();
    }

    /**
     * @param string $commandName
     *
     * @throws \Jeekens\Console\Exception\Exception
     * @throws \Jeekens\Console\Exception\UnknownColorException
     */
    protected function command(string $commandName)
    {
        $commandInfos = Command::getCommandInfos();
        if (isset($commandInfos[$commandName]) && $command = $commandInfos[$commandName]) {
            $group = Command::getCommandGroup();
            $table = new Table();
            $table->addHeader(sprintf('<cyan>%s</cyan>', $commandName));
            $globalOpts = Command::getBindOpts();
            $groupOpts = Command::getGroupBindOpts();

            if (trim($command['describe']) != '') {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Describe</yellow>:')
                    ->addRow()
                    ->addColumn('  ' . $command['describe']);
            }

            if (trim($command['usage']) != '') {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Usage</yellow>:')
                    ->addRow()
                    ->addColumn('  ' . $command['usage']);
            }

            if (! empty($command['arguments'])) {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Arguments</yellow>:');

                $i = 1;

                foreach ($command['arguments'] as $des) {
                    $table->addRow()
                        ->addColumn(sprintf(' <green>arg%s</green>', $i))
                        ->addColumn('  ' . $des);
                }
            }

            if (!empty($globalOpts)) {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Global Options</yellow>:');

                foreach ($globalOpts as $opt => $des) {
                    $table->addRow()
                        ->addColumn(sprintf(' <green>%s</green>', $opt))
                        ->addColumn($des);
                }

            }

            if (!empty($group) && isset($groupOpts[$group]) && ($go = $groupOpts[$group])) {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Group Global Options</yellow>:');

                foreach ($go as $opt => $des) {
                    $table->addRow()
                        ->addColumn(sprintf(' <green>%s</green>', $opt))
                        ->addColumn($des);
                }
            }

            if (! empty($command['optionsDetails'])) {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Options</yellow>:');

                foreach ($command['optionsDetails'] as $opt => $des) {
                    $table->addRow()
                        ->addColumn(sprintf(' <green>%s</green>', $opt))
                        ->addColumn($des);
                }
            }

            if (trim($command['example']) != '') {
                $table->addTable()
                    ->addRow(null, true)
                    ->addColumn('<yellow>Example</yellow>:')
                    ->addRow()
                    ->addColumn('  ' . $command['example']);
            }

            $table->hideBorder()
                ->display();
        } else {
            Command::error(sprintf('Command "%s" not found.', $commandName), true);
        }
    }

}