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
     * @var string|null
     */
    private $forwardCommand;

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
     * 初始化并创建一个命令行工具对象，此时可以注入全局命令
     *
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
     * 获取一个命令行工具对象，如果不存在则自动创建
     *
     * @return Command
     *
     * @throws CommandNameParseException
     */
    public static function getCommand()
    {
        return self::createCommand();
    }

    /**
     * 获取当前命令的名称，此方法只有boot后才能获取到正确的值
     *
     * @return string|null
     *
     * @throws CommandNameParseException
     */
    public static function getCommandName(): ?string
    {
        return self::getCommand()->returnCommandName();
    }

    /**
     * 获取全部命令信息
     *
     * @return array
     *
     * @throws CommandNameParseException
     */
    public static function getCommandInfos()
    {
        return self::getCommand()->commands;
    }

    /**
     * 获取全部支持的命令
     *
     * @return array
     *
     * @throws CommandNameParseException
     */
    public static function getCommandNames()
    {
        return array_keys(self::getCommandInfos());
    }

    /**
     * 获取当前的命令程序入口脚本
     *
     * @return string|null
     *
     * @throws CommandNameParseException
     */
    public static function getScript()
    {
        return self::getCommand()
            ->input()
            ->getScript();
    }

    /**
     * 获取短选项值
     *
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     *
     * @throws CommandNameParseException
     */
    public static function getShortOpts($key = null, $default = null)
    {
        return self::getCommand()
            ->input()
            ->getShortOpts($key, $default);
    }

    /**
     * 获取长选项值
     *
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     *
     * @throws CommandNameParseException
     */
    public static function getLongOpts($key = null, $default = null)
    {
        return self::getCommand()
            ->input()
            ->getLongOpts($key, $default);
    }

    /**
     * 判断选项是否存在，不区分长选项还是短选项
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws CommandNameParseException
     */
    public static function hasOpt(string $key)
    {
        return self::getCommand()
            ->input()
            ->hasArrayOpts($key);
    }

    /**
     * 判断多个选项是否存在，当其中一个存在是则返回true，这对于-n, --name这种长选项+短选项的形式有很大的帮助
     *
     * @param array $keys
     *
     * @return bool
     *
     * @throws CommandNameParseException
     */
    public static function hasOneOpt(array $keys)
    {
        return self::getCommand()
            ->input()
            ->hasOneOpts($keys);
    }

    /**
     * 获取数组选项值
     *
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     *
     * @throws CommandNameParseException
     */
    public static function getArrayOpts($key = null, $default = null)
    {
        return self::getCommand()
            ->input()
            ->getArrayOpts($key, $default);
    }

    /**
     * 获取命令输入的参数
     *
     * @param null $key
     * @param null $default
     *
     * @return array|mixed|null
     *
     * @throws CommandNameParseException
     */
    public static function getParam($key = null, $default = null)
    {
        if (is_numeric($key) || is_string($key)) {
            return null;
        }

        if ($key === null) {
            return self::getCommand()
                ->args;
        } else {
            return self::getCommand()
                    ->args[$key] ?? $default;
        }
    }

    /**
     * 获取当前目录
     *
     * @return string|null
     *
     * @throws CommandNameParseException.
     */
    public static function getPwd()
    {
        return self::getCommand()
            ->input()
            ->getPwd();
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
     * 注册一个闭包命令
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
     * 启动命令行工具
     *
     * @throws CommandNotFoundException
     * @throws Throwable
     */
    public static function boot()
    {
        return self::getCommand()->bootstrap();
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
            $result= true;
            $this->parseCommand();

            if (! empty($this->forwardCommand)) {
                if (($forwardCommand = $this->commands[$this->forwardCommand] ?? null) ||
                    ($forwardCommand = $this->baseCommands[$this->forwardCommand] ?? null)) {
                    if ($forwardCommand['command'] instanceof Closure) {
                        $result = $forwardCommand['command']();
                    } else {
                        $result = $this->commandHandle($forwardCommand);
                    }
                }
            }

            if ($result === false) {
                return false;
            }

            if (! empty($this->command) && ($command = $this->commands[$this->command] ?? null) ||
                ($command = $this->baseCommands[$this->command] ?? null)) {

                if ($command['command'] instanceof Closure) {
                    return $command['command']();
                } else {
                    return $this->commandHandle($command);
                }

            } elseif (! empty($this->command)) {
                throw new CommandNotFoundException(sprintf('Command "%s" Notfound.', $this->command));
            }

            return $result;
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
        $args = $this->input()
            ->getArgs();
        $key = null;

        reset($args);

        while (1) {
            $key = key($args);
            if ($key === null) break;

            if (is_int($key)) {
                $command = current($args);
                break;
            } else {
                next($args);
                continue;
            }

        }

        if (!empty($command)) {
            $this->command = $command;
            unset($args[$key]);
            $this->args = $args;
        } else {
            $this->forwardCommand = 'help';
        }
    }

    /**
     * 命令执行方法
     *
     * @param $commandHandleArray
     *
     * @return bool
     *
     * @throws CommandNameParseException
     */
    private function commandHandle($commandHandleArray)
    {
        /**
         * @var $command CommandInterface
         */
        $command = $commandHandleArray['command'];
        $options = $commandHandleArray['options'];
        $result = true;

        foreach ($options as $option) {
            if (is_string($option)) {
                $callback = $option . 'Action';
                if (method_exists($command, $callback)
                    && self::hasOpt($option)) {
                    $result = $command->$callback();
                }
            } elseif (is_array($option)) {
                foreach ($option as $alias) {
                    $callback = $alias . 'Action';
                    if (method_exists($command, $callback)
                        && self::hasOneOpt($option)) {
                        $result = $command->$callback();
                        break 1;
                    }
                }
            }

            if ($result === false) {
                return false;
            }
        }

        if ($result !== false) {
            return $command->handle();
        } else {
            return false;
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

    /**
     * 返回输入流对象
     *
     * @return Input|null
     */
    private function input()
    {
        return $this->input;
    }

    /**
     * 返回输出流对象
     *
     * @return Output|null
     */
    private function output()
    {
        return $this->output;
    }

}