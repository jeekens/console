<?php declare(strict_types=1);


namespace Jeekens\Console;


use Closure;
use Throwable;
use Jeekens\Console\Input\Input;
use Jeekens\Console\Output\Output;
use Jeekens\Console\Output\Modifier;
use Jeekens\Console\Command\HelpCommand;
use Jeekens\Console\Exception\InputCommandFormatException;

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
    private static $globalOptions = [
        '-h, --help' => 'help',
        '--no-style' => 'noStyle',
    ];

    /**
     * @var array
     */
    private static $groupGlobalOptions = [];

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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Throwable
     */
    public static function boot()
    {
        return self::getCommand()
            ->bootstrap();
    }

    /**
     * 向控制台输出一段绿色的文字
     *
     * @param string $info
     * @param bool|int $quit
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function info(string $info, $quit = false)
    {
        return self::styleLine($info, Modifier::COLOR_GREEN, null, null, $quit);
    }

    /**
     * 输出一个无格式的文本
     *
     * @param string $info
     * @param bool|int $quit
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function line(string $info, $quit = false)
    {
        return self::write($info, true, $quit);
    }

    /**
     * 输出数据
     *
     * @param $messages
     * @param bool $nl
     * @param bool|int $quit
     * @param bool $isErr
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function write($messages, $nl = true, $quit = false, bool $isErr = false)
    {
        return self::getCommand()->output()
            ->write($messages, $nl, $quit, $isErr);
    }

    /**
     * 向控制台输出一段红色的文字
     *
     * @param string $info
     * @param bool|int $quit
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function error(string $info, $quit = false)
    {
        return self::styleLine($info, Modifier::COLOR_RED, null, null, $quit);
    }

    /**
     * 向控制台输出一段黄色的文字
     *
     * @param string $info
     * @param bool|int $quit
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function warning(string $info, $quit = false)
    {
        return self::styleLine($info, Modifier::COLOR_YELLOW, null, null, $quit);
    }

    /**
     * 输出一段带有样式的文字
     *
     * @param string $info
     * @param string|null $fg
     * @param string|null $bg
     * @param array|null $style
     * @param bool|int $quit
     *
     * @return int
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function styleLine(string $info, string $fg = null, string $bg = null, ?array $style = null, $quit = false)
    {
        return self::write(modifier($info, $fg, $bg, $style), true, $quit);
    }

    /**
     * 询问用户并返回用户输入值，为空时返回默认值
     *
     * @param string $question
     * @param string $default
     *
     * @return bool|string
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function ask(string $question, string $default = '')
    {
        if (!$question = trim($question)) {
            self::error('Question is empty!', 1);
        }

        if ($default !== '') {
            $msg = ucfirst($question) . \modifier(' (Default: ' . $default . ')', Modifier::COLOR_GREEN);
        } else {
            $msg = ucfirst($question);
        }

        self::line($msg);
        $value = self::read();

        if ($value === '') {
            return $default;
        } else {
            return $value;
        }
    }

    /**
     * 请求用户确认信息，只能输入yes(y) no(n)不区分大小写，如果输出有误则重新询问
     *
     * @param string $message
     * @param int $limit
     *
     * @return bool|mixed
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function confirm(string $message, int $limit = 0)
    {
        if (!$message = trim($message)) {
            self::error('Message is empty!', 1);
        }

        $confirm = ['y' => true, 'yes' => true, 'no' => false, 'n' => false,];
        $message = ucfirst($message) . \modifier(' [yes(y) OR no(n)]', Modifier::COLOR_GREEN);
        $i = 0;

        confirm:

        $i++;
        self::line($message);
        $value = strtolower(self::read(3));

        if (isset($confirm[$value])) {
            return $confirm[$value];
        }

        if ($limit == 0 || $i < $limit) goto confirm;

        return false;
    }

    /**
     * 多重选择
     *
     * @param string $question
     * @param array $choices
     * @param int|null $defaultIndex
     *
     * @return bool|int|string|null
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function choice(string $question, array $choices, ?int $defaultIndex = null)
    {
        if (!$question = trim($question)) {
            self::error('Question is empty!', 1);
        }

        if (empty($choices)) {
            self::error('Choices is empty!', 1);
        }

        self::line(ucfirst($question));
        $i = 1;

        foreach ($choices as $value) {
            self::line(
                \modifier("({$i}). ", Modifier::COLOR_GREEN).$value
            );
            $i++;
        }

        $index = self::read(3);

        if (isset($choices[$index])) {
            return $index;
        } else {
            return $defaultIndex;
        }
    }

    /**
     * 循环提问，并用闭包对输入值进行验证，如果传入第三个参数则表示循环多少次后则强制退出询问
     *
     * @param string $question
     * @param Closure $closure
     * @param int $limit
     *
     * @return bool|string
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function loopAsk(string $question, Closure $closure, int $limit = 0)
    {
        if (!$question = trim($question)) {
            self::error('Question is empty!', 1);
        }

        $question = ucfirst($question);
        $error = null;
        $i = 0;

        ask:

        $i++;
        $quit = false;
        self::line($question);
        $value = self::read();

        if ($closure($value, $error, $quit)) {
            return $value;
        }

        if (is_string($error) && $error !== '') {
            self::warning($error, $quit);
        }

        if ($limit == 0 || $i < $limit) goto ask;

        return $value;
    }



    /**
     * @see https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
     *
     * @param $prompt
     *
     * @return array|string
     */
    private static function hideInput($prompt)
    {
        $prompt = $prompt ? addslashes($prompt) : 'Enter:';

        // at windows cmd.
        if (preg_match('/^win/i', PHP_OS)) {
            $vbFile = sys_get_temp_dir() . '/hidden_prompt_input.vbs';

            file_put_contents($vbFile, sprintf('wscript.echo(InputBox("%s", "", "password here"))', $prompt));

            $command  = 'cscript //nologo ' . escapeshellarg($vbFile);
            $password = rtrim(shell_exec($command));
            unlink($vbFile);

            return $password;
        }

        // linux, unix, git-bash
        if (scripted()) {
            // COMMAND: sh -c 'read -p "Enter Password:" -s user_input && echo $user_input'
            $command  = sprintf('sh -c "read -p \'%s\' -s user_input && echo $user_input"', $prompt);
            $password = script($command, false);

            print "\n";
            return $password;
        }

        throw new \RuntimeException('Can not invoke bash shell env');
    }

    /**
     * 读取输入流中的数据
     *
     * @param int $length
     *
     * @return bool|string
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function read(int $length = 1024)
    {
        return self::getCommand()
            ->input()
            ->read($length);
    }

    /**
     * @return bool
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public function bootstrap()
    {
        if (!$this->isBoot()) {
            $this->isBoot = true;
            $result = true;
            $this->parseCommand();

            if (!empty($this->forwardCommand)) {
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

            if (!empty($this->command) && ($command = $this->commands[$this->command] ?? null) ||
                ($command = $this->baseCommands[$this->command] ?? null)) {

                if ($command['command'] instanceof Closure) {
                    return $command['command']();
                } else {
                    return $this->commandHandle($command);
                }

            } elseif (!empty($this->command)) {
                self::error(
                    sprintf('Command %s Notfound.', \modifier($this->command, Modifier::COLOR_RED, Modifier::COLOR_WHITE)),
                    1
                );
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    private function __construct($globalCommand = null, ?Input $input = null, ?Output $output = null)
    {
        if (PHP_SAPI != 'cli') {
            exit(0);
        }

        try {
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
        } catch (InputCommandFormatException $e) {
            echo \modifier($e->getMessage(), Modifier::COLOR_RED);
            die(1);
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
     * @param $describe
     * @param $usage
     * @param $arguments
     * @param $options
     * @param $example
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
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
     * @param CommandInterface $command
     * @param bool $isGlobal
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    private function addCommand(CommandInterface $command, bool $isGlobal = false)
    {
        $commandName = $this->commandNameFilter(
            $this->getCommandProperty($command, 'name')
        );

        if (empty($commandName)) {
            self::error(
                sprintf('Command class "%s" name is empty or does not exist.',
                    \modifier(get_class($command), Modifier::COLOR_RED, Modifier::COLOR_WHITE
                    )), 1);
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
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    private function commandNameParse(string $name): array
    {
        $info = explode(':', $name, 2);

        if (empty($info) || empty($info[1])) {
            self::error(
                sprintf('Command name "%s" is unsupported. Must be "groupName:commandName".',
                    \modifier($name, Modifier::COLOR_RED, Modifier::COLOR_WHITE)),
                1
            );
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