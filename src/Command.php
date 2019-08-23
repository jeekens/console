<?php declare(strict_types=1);


namespace Jeekens\Console;


use function array_key_exists;
use function array_merge;
use function class_get;
use Closure;
use Jeekens\Console\Command\NoStyleCommand;
use Jeekens\Console\Output\Style;
use Throwable;
use Jeekens\Basics\Fs;
use Jeekens\Basics\Os;
use Jeekens\Console\Input\Input;
use Jeekens\Console\Output\Output;
use Jeekens\Console\Command\HelpCommand;
use Jeekens\Console\Exception\InputCommandFormatException;
use function key;
use function copy;
use function exec;
use function to_array;
use function trim;
use function reset;
use function ltrim;
use function getenv;
use function unlink;
use function strpos;
use function current;
use function explode;
use function implode;
use function sprintf;
use function ucfirst;
use function ob_start;
use function var_dump;
use function is_array;
use function get_class;
use function is_string;
use function array_keys;
use function is_numeric;
use function preg_match;
use function strtolower;
use function ob_end_clean;
use function preg_replace;
use function method_exists;
use function ob_get_contents;

/**
 * Class Command
 *
 * @package Jeekens\Console
 */
final class Command
{

    private static $screenSize;

    /**
     * @var string|null
     */
    private $command;

    /**
     * @var string|null
     */
    private $fullCommand;

    /**
     * @var string|null
     */
    private $group;

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
        NoStyleCommand::class,
        HelpCommand::class,
    ];

    /**
     * @var array
     */
    private $globalOptionsMap = [];

    /**
     * @var array
     */
    private $groupGlobalOptionsMap = [];

    /**
     * @var array
     */
    private $groupGlobalOptions = [];

    /**
     * @var array
     */
    private $globalOptions = [];

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
                $globalCommand = self::$defaultGlobalCommands;
            }

            self::$_singleton = new self($globalCommand, $input, $output);

            if ($globalCommand) {
                $commands = to_array($globalCommand);
                foreach ($commands as $command) {

                    if (is_string($command)) {
                        $command = new $command();
                    }

                    self::$_singleton->addCommand($command, true);
                }
            }
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
     * 开启输出缓冲
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function enableBuffer()
    {
        self::getCommand()
            ->output()
            ->enableBuffer();
    }

    /**
     * 关闭输出缓冲
     *
     * @param bool $isFlush
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function disableBuffer(bool $isFlush = true)
    {
        self::getCommand()
            ->output()
            ->disableBuffer($isFlush);
    }

    /**
     * 判断是否开启输出缓冲区
     *
     * @return bool
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function isEnableBuffer()
    {
        return self::getCommand()
            ->output()
            ->isEnableBuff();
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
        return self::getCommand()->command;
    }

    /**
     * 获取完整的命令名称，此方法只有boot后才能获取到正确的值
     *
     * @return string|null
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function getFullCommandName(): ?string
    {
        return self::getCommand()->fullCommand;
    }

    /**
     * 获取命令的分组名，此方法只有boot后才能获取到正确的值
     *
     * @return string|null
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function getCommandGroup(): ?string
    {
        return self::getCommand()->group;
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
     * 获取全局参数绑定信息
     *
     * @return array
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function getBindOpts()
    {
        return self::getCommand()
            ->globalOptions;
    }

    /**
     * 获取分组参数绑定信息
     *
     * @return array
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function getGroupBindOpts()
    {
        return self::getCommand()
            ->groupGlobalOptions;
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
        if (!is_numeric($key) && !is_string($key)) {
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
     * 设置命令参数
     *
     * @param array $param
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function setParam(array $param)
    {
        self::getCommand()->args = $param;
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

        $commands = to_array($command);

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
     * 回收资源
     */
    public static function recovery()
    {
        self::$_singleton = null;
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
        return self::write(sprintf('<green>%s</green>', $info), true, $quit);
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
    public static function line(string $info = '', $quit = false)
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
        return self::write(sprintf('<red>%s</red>', $info), true, $quit);
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
        return self::write(sprintf('<yellow>%s</yellow>', $info), true, $quit);
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
            $msg = ucfirst($question) . sprintf(' <green>(Default: %s)</green>', $default);
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
        $message = ucfirst($message) . '<green>[yes(y) OR no(n)]</green>';
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
                sprintf(' <green>(%d).</green> %s', $i, $value)
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
     * 清屏
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function clear()
    {
        if (Os::systemHasAnsiSupport(Os::isWin())) {
            self::getCommand()
                ->output()
                ->write("\033[H\033[2J", false);
        }
    }

    /**
     * @param $data
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function dump($data)
    {
        ob_start();

        var_dump($data);

        $result = ob_get_contents();

        ob_end_clean();

        self::getCommand()
            ->output()
            ->write($result);
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
     * 隐藏用户输入内容
     *
     * @param string $question
     *
     * @return array|string
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function askHide(string $question)
    {
        self::line($question);
        return self::hideInput();
    }

    /**
     * 执行一个命令
     *
     * @param string $commandName
     *
     * @return bool
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    public static function execute(string $commandName)
    {
        return self::getCommand()
            ->call($commandName);
    }

    /**
     * 隐藏用户输入内容
     *
     * @return array|string
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    private static function hideInput()
    {
        // at windows cmd.
        if (Os::isWin()) {
            $exe = __DIR__ . './Resources/hiddeninput.exe';
            $tmpExe = Fs::getTmpDir() . '/hiddeninput.exe';

            copy($exe, $tmpExe);

            $exe = $tmpExe;

            $output = trim(Os::script($exe, false));
            // clean up
            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            self::line('');

            return $output;
        }

        // linux, unix, git-bash
        if (($shell = Os::getShell())) {
            $readCmd = ($shell === 'csh') ? 'set mypassword = $<' : 'read -r mypassword';
            $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $output = OS::script($command, false);
            if ($output !== null) {
                self::line('');
                return trim($output);
            }
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
    public static function read(int $length = 4096)
    {
        return self::getCommand()
            ->input()
            ->read($length);
    }

    /**
     * 返回终端屏幕大小
     *
     * @param bool $refresh
     *
     * @return array|bool
     */
    public static function getScreenSize(bool $refresh = false)
    {
        if (self::$screenSize !== null && !$refresh) {
            return self::$screenSize;
        }

        if (Os::getShell()) {
            // try stty if available
            $stty = [];

            if (exec('stty -a 2>&1', $stty)
                && preg_match('/rows\s+(\d+);\s*columns\s+(\d+);/mi', implode(' ', $stty), $matches)
            ) {
                return (self::$screenSize = [$matches[2], $matches[1]]);
            }

            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int)exec('tput cols 2>&1')) > 0 && ($height = (int)exec('tput lines 2>&1')) > 0) {
                return (self::$screenSize = [$width, $height]);
            }

            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int)getenv('COLUMNS')) > 0 && ($height = (int)getenv('LINES')) > 0) {
                return (self::$screenSize = [$width, $height]);
            }
        }

        if (Os::isWin()) {
            $output = [];
            exec('mode con', $output);

            if (isset($output[1]) && strpos($output[1], 'CON') !== false) {
                return (self::$screenSize = [
                    (int)preg_replace('~\D~', '', $output[3]),
                    (int)preg_replace('~\D~', '', $output[4])
                ]);
            }
        }

        return (self::$screenSize = false);
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
            $currentCommand = $this->fullCommand;
            $currentGroup = $this->group;

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

            $buildCommand = [];
            $optionBind = $this->globalOptionsMap;

            if (!empty($this->groupGlobalOptionsMap[$currentGroup])) {
                $optionBind = array_merge($optionBind, $this->groupGlobalOptionsMap[$currentGroup]);
            }

            foreach ($optionBind as $key => $command) {
                if ($this->input()
                        ->hasArrayOpts($key) && !array_key_exists($command, $buildCommand)) {

                    $buildCommand[$command] = $this->call($command);

                    if ($buildCommand[$command] === false) {
                        return false;
                    }

                }
            }

            return $this->call($currentCommand);
        }

        return null;
    }

    /**
     * @param string $currentCommand
     *
     * @return bool
     *
     * @throws Exception\Exception
     * @throws Exception\UnknownColorException
     */
    private function call($currentCommand)
    {
        if (!empty($currentCommand) && ($command = $this->commands[$currentCommand] ?? null) ||
            ($command = $this->baseCommands[$currentCommand] ?? null)) {

            if ($command['command'] instanceof Closure) {
                return $command['command']();
            } else {
                return $this->commandHandle($command);
            }

        } elseif (!empty($currentCommand)) {
            self::error(
                sprintf('Command <background_white>%s</background_white> Notfound.', $currentCommand),
                1
            );
        }

        return null;
    }

    /**
     * 解析命令
     */
    private function parseCommand()
    {
        $args = $this->input()
                ->getArgs() ?? [];

        reset($args);
        $command = current($args);
        $key = key($args);

        if (!empty($command)) {
            $this->fullCommand = $command;
            $tmp = explode(':', $command, 2);

            if (count($tmp) > 1) {
                [$this->group, $this->command] = $tmp;
            }

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
        } catch (InputCommandFormatException $e) {
            if (Style::isEnableAnsi()) {
                $error = Style::tags()->apply(sprintf('<red>%s</red>', $e->getMessage()));
            } else {
                $error = $e->getMessage();
            }
            echo $error;
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
            'describe' => $describe ?? '',
            'usage' => $usage ?? '',
            'arguments' => $arguments ?? [],
            'optionsDetails' => $options ?? [],
            'example' => $example ?? '',
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
        $name = (string)class_get($command, 'name');
        $commandName = $this->commandNameFilter($name);

        if (empty($commandName)) {
            self::error(
                sprintf('Command class <background_white>"%s"</background_white> name is empty or does not exist.',
                    get_class($command)), 1);
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
            'optionsDetails' => class_get($command, 'options') ?? [],
            'describe' => class_get($command, 'describe') ?? '',
            'usage' => class_get($command, 'usage') ?? '',
            'arguments' => class_get($command, 'arguments') ?? [],
            'example' => class_get($command, 'example') ?? '',
            'bindOpts' => class_get($command, 'bindOpts') ?? [],
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
        $commandName = class_get($command, 'name');
        $options = class_get($command, 'options');
        $bindOption = class_get($command, 'bindOpts');
        $usage = class_get($command, 'usage');
        $canCallback = [];
        $groupName = '';

        if (strpos($commandName, ':')) {
            $groupName = explode(':', $commandName)[0];
        }

        if (!empty($bindOption)) {
            if (empty($groupName)) {
                foreach ($bindOption as $item) {
                    if (strpos($item, ',')) {
                        $names = explode(',', $item, 2);
                        foreach ($names as $name) {
                            $option = ltrim(ltrim($this->commandNameFilter($name), '-'), '-');
                            $this->globalOptionsMap[$option] = $commandName;
                        }
                    } else {
                        $option = ltrim(ltrim($this->commandNameFilter($item), '-'), '-');
                        $this->globalOptionsMap[$option] = $commandName;
                    }
                    $this->globalOptions[$item] = $usage;
                }
            } else {
                foreach ($bindOption as $item) {
                    if (strpos($item, ',')) {
                        $names = explode(',', $item, 2);
                        foreach ($names as $name) {
                            $option = ltrim(ltrim($this->commandNameFilter($name), '-'), '-');
                            $this->groupGlobalOptionsMap[$option] = $commandName;
                        }
                    } else {
                        $option = ltrim(ltrim($this->commandNameFilter($item), '-'), '-');
                        $this->groupGlobalOptionsMap[$option] = $commandName;
                    }
                    $this->groupGlobalOptions[$item] = $usage;
                }
            }
        }

        if (!empty($options) && is_array($options)) {
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
                sprintf('Command name <background_white>"%s"</background_white> is unsupported. Must be "groupName:commandName".',
                    $name),
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
            "/\s+/",
            '',
            $string
        );
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