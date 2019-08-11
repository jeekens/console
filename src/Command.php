<?php declare(strict_types=1);


namespace Jeekens\Console;


use Closure;
use Throwable;
use Jeekens\Console\Input\Input;
use Jeekens\Console\Output\Output;
use Jeekens\Console\Command\HelpCommand;
use Jeekens\Console\Exception\CommandNotFoundException;
use Jeekens\Console\Exception\CommandNameParseException;

/**
 * Class Command
 *
 * @package Jeekens\Console
 */
final class Command
{

    /**
     * @var string|null
     */
    private $command;

    /**
     * @var array|null
     */
    private $commandGroup;

    /**
     * @var array
     */
    private $commands;

    /**
     * @var array
     */
    private $baseCommands;

    /**
     * @var Input
     */
    private $input;

    /**
     * @var Output
     */
    private $output;

    /**
     * @var bool
     */
    private $isBoot = false;

    /**
     * @var array
     */
    private $args = [];

    /**
     * @var array
     */
    private static $defaultGlobalCommands = [
        HelpCommand::class,
    ];

    /**
     * @var array
     */
    private static $defaultGlobalOptions = [
        '-h, --help' => 'help',
    ];

    /**
     * @var array
     */
    private static $globalOptions = [];

    /**
     * @var self;
     */
    private static $_singleton;

    /**
     * @param null $globalCommand
     * @param Input|null $input
     * @param Output|null $output
     *
     * @return Command
     *
     * @throws CommandNameParseException
     */
    public static function createCommand($globalCommand = null, ?Input $input = null, ?Output $output = null)
    {
        if (!(self::$_singleton instanceof self)) {

            if (empty($globalCommand)) {
                foreach (self::$defaultGlobalCommands as $command) {
                    $globalCommand[] = new $command();
                }
            }

            self::$_singleton = new self($globalCommand, $input, $output);
        }

        return self::$_singleton;
    }

    /**
     * @return Command
     *
     * @throws CommandNameParseException
     */
    public static function getCommand()
    {
        return self::createCommand();
    }

    /**
     * @return string|null
     *
     * @throws CommandNameParseException
     */
    public static function getCommandName(): ?string
    {
        return self::getCommand()->returnCommandName();
    }

    /**
     * @return array
     *
     * @throws CommandNameParseException
     */
    public static function getCommandInfos()
    {
        return self::getCommand()->commands;
    }

    /**
     * @return array
     *
     * @throws CommandNameParseException
     */
    public static function getCommandNames()
    {
        return array_keys(self::getCommandInfos());
    }

    /**
     * @return string|null
     *
     * @throws CommandNameParseException
     */
    public static function getScript()
    {
        return self::getCommand()->input
            ->getScript();
    }

    /**
     * @return string|null
     *
     * @throws CommandNameParseException.
     */
    public static function getPwd()
    {
        return self::getCommand()->input
            ->getPwd();
    }



    public static function option(string $key, $default = null)
    {
        //TODO Get option value by key.
    }

    /**
     * 注册命令
     *
     * @param $command
     *
     * @throws CommandNameParseException
     */
    public static function registerCommand($command)
    {
        $singleton = self::getCommand();
        if ($singleton->isBoot()) return;
        $commands = [];

        if (!is_array($command)) {
            $commands[] = $command;
        } else {
            $commands = $command;
        }

        foreach ($commands as $command) {
            $singleton->addCommand($command);
        }
    }

    /**
     * @param $name
     * @param $closure
     * @param string|null $describe
     * @param string|null $usage
     * @param array|null $arguments
     * @param array|null $options
     * @param string|null $example
     *
     * @throws CommandNameParseException
     */
    public static function registerClosureCommand(
        $name,
        $closure,
        ?string $describe = null,
        ?string $usage = null,
        ?array $arguments = null,
        ?array $options = null,
        ?string $example = null
    )
    {
        if (self::getCommand()->isBoot()) return;

        self::getCommand()->addClosureCommand(
            $name,
            $closure,
            $describe,
            $usage,
            $arguments,
            $options,
            $example
        );
    }

    /**
     * @throws CommandNotFoundException
     * @throws Throwable
     */
    public static function boot()
    {
        self::getCommand()->bootstrap();
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws CommandNameParseException
     */
    public static function hasOption(string $key): bool
    {
        return self::getCommand()->isHaveOption($key);
    }

    /**
     * @throws CommandNotFoundException
     *
     * @throws Throwable
     */
    public function bootstrap()
    {
        if (!$this->isBoot()) {
            $this->isBoot = true;
            $this->parseCommand();

            if (($command = $this->commands[$this->command] ?? null) ||
                ($command = $this->baseCommands[$this->command] ?? null)) {

                if ($command['command'] instanceof Closure) {
                    $command['command']();
                } else {
                    $this->commandHandle($command);
                }

            } else {
                throw new CommandNotFoundException(sprintf('Command "%s" Notfound.', $this->command));
            }
        }
    }

    /**
     * 获取命令属性
     *
     * @param $command
     * @param string $name
     *
     * @return mixed
     */
    private function getCommandProperty($command, string $name)
    {
        if (property_exists($command, $name)) {
            return $command->$name;
        } elseif (method_exists($command, $name)) {
            return $command->$name();
        }
        return null;
    }

    /**
     * 解析命令
     */
    private function parseCommand()
    {
        $input = $this->input;
        $args = $input->getArgs();

        if (!empty($args)) {
            $this->command = array_shift($args);
            $this->args = $args;
        } else {
            $this->command = 'help';
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function isHaveOption(string $key): bool
    {
        $options = $this->commands[$this->command]['options'] ?? [];

        if (empty($this->command) || empty($options)) {
            return $this->input->hasArrayOpts($key);
        }

        foreach ($options as $option) {
            if (is_array($option) && array_search($key, $option) !== false) {
                foreach ($option as $item) {
                    if ($this->input->hasArrayOpts($item)) {
                        return true;
                    }
                }
                return false;
            }
        }

        return $this->input->hasArrayOpts($key);
    }

    /**
     * 处理命令
     *
     * @param $commandHandleArray
     *
     * @throws Throwable
     */
    private function commandHandle($commandHandleArray)
    {
        /**
         * @var $command CommandInterface
         */
        $command = $commandHandleArray['command'];
        $options = $commandHandleArray['options'];
        $exceptionHandle = $this->getCommandProperty($command, 'exceptionHandle');

        try {

            foreach ($options as $option) {
                if (is_string($option)) {
                    $callback = $option . 'Action';
                    if (method_exists($command, $callback)
                        && $this->isHaveOption($option)) {
                        $command->$callback();
                    }
                } elseif (is_array($option)) {
                    foreach ($option as $alias) {
                        $callback = $alias . 'Action';
                        if (method_exists($command, $callback)
                            && $this->isHaveOption($alias)) {
                            $command->$callback();
                            break 1;
                        }
                    }
                }
            }

            $command->handle();

        } catch (Throwable $e) {
            if (is_string($exceptionHandle) && class_exists($exceptionHandle)) {
                /**
                 * @var $handle ExceptionHandle
                 */
                $handle = new $exceptionHandle;
                $result = $handle->handle($e);

                if ($result instanceof Throwable) {
                    throw $result;
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * 判断命令是否已经启动
     *
     * @return bool
     */
    public function isBoot(): bool
    {
        return $this->isBoot;
    }

    /**
     * Command constructor.
     *
     * @param null $globalCommand
     * @param Input|null $input
     * @param Output|null $output
     *
     * @throws CommandNameParseException
     */
    private function __construct($globalCommand = null, ?Input $input = null, ?Output $output = null)
    {
        if (PHP_SAPI != 'cli') {
            exit(0);
        }

        if ($input === null) {
            $input = new Input();
        }

        if (empty($output)) {
            $output = new Output();
        }

        if ($globalCommand) {
            $commands = [];

            if (!is_array($globalCommand)) {
                $commands[] = $globalCommand;
            } else {
                $commands = $globalCommand;
            }

            foreach ($commands as $command) {
                $this->addCommand($command, true);
            }
        }

        $this->input = $input;
        $this->output = $output;
    }

    private function __clone()
    {
    }

    /**
     * 添加一个闭包命令
     *
     * @param $name
     * @param $closure
     * @param string|null $describe
     * @param string|null $usage
     * @param array|null $arguments
     * @param array|null $options
     * @param string|null $example
     *
     * @throws CommandNameParseException
     */
    private function addClosureCommand(
        $name,
        $closure,
        $describe,
        $usage,
        $arguments,
        $options,
        $example
    )
    {
        $commandName = $this->commandNameFilter($name);

        if (isset($this->commands[$commandName])) {
            return;
        }

        [$group, $name] = $this->commandNameParse($commandName);
        $this->groupRegisterCommand($group, $name);
        $this->commands[$commandName] = [
            'command' => $closure,
            'describe' => $describe,
            'usage' => $usage,
            'arguments' => $arguments,
            'optionsDetails' => $options,
            'example' => $example,
        ];
    }

    /**
     * 添加一个常规命令
     *
     * @param CommandInterface $command
     * @param $isGlobal
     *
     * @throws CommandNameParseException
     */
    private function addCommand(CommandInterface $command, bool $isGlobal = false)
    {
        $commandName = $this->commandNameFilter(
            $this->getCommandProperty($command, 'name')
        );

        if (empty($commandName)) {
            throw new CommandNameParseException(sprintf('Command class "%s" name is empty or does not exist.', get_class($command)));
        }

        if (isset($this->commands[$commandName])) {
            return;
        }

        if (!$isGlobal) {
            [$group, $name] = $this->commandNameParse($commandName);
            $this->groupRegisterCommand($group, $name);
        }

        $this->commands[$commandName] = [
            'command' => $command,
            'options' => $this->commandOptionsParse($command),
        ];
    }

    /**
     * 命令参数解析
     *
     * @param $command
     *
     * @return array
     */
    private function commandOptionsParse($command): array
    {
        $options = $this->getCommandProperty($command, 'options');
        $canCallback = [];

        if (is_array($options)) {
            $keys = array_keys($options);
            foreach ($keys as $key) {
                if (strpos($key, ',')) {
                    $names = explode(',', $key, 2);
                    $callbacks = [];
                    foreach ($names as $name) {
                        $callbacks[] = ltrim(ltrim($this->commandNameFilter($name), '-'), '-');
                    }
                    $canCallback[$key] = $callbacks;
                } else {
                    $canCallback[$key] = ltrim(ltrim($this->commandNameFilter($key), '-'), '-');
                }
            }
        }

        return $canCallback;
    }

    /**
     * @param string $name
     *
     * @return array
     *
     * @throws CommandNameParseException
     */
    private function commandNameParse(string $name): array
    {
        $info = explode(':', $name, 2);

        if (empty($info) || empty($info[1])) {
            throw new CommandNameParseException(sprintf('Command name "%s" is unsupported. Must be "groupName:commandName".', $name));
        }

        return $info;
    }

    /**
     * @param string $group
     * @param string $name
     */
    private function groupRegisterCommand(string $group, string $name)
    {
        $this->commandGroup[$group][] = $name;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function commandNameFilter(string $string): string
    {
        return preg_replace(
            "/(\t|\n|\v|\f|\r| |\xC2\x85|\xc2\xa0|\xe1\xa0\x8e|\xe2\x80[\x80-\x8D]|\xe2\x80\xa8|\xe2\x80\xa9|\xe2\x80\xaF|\xe2\x81\x9f|\xe2\x81\xa0|\xe3\x80\x80|\xef\xbb\xbf)+/",
            '',
            $string
        );
    }

    /**
     * @return string|null
     */
    private function returnCommandName()
    {
        return $this->command;
    }

}