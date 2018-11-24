<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Symfony\Component\Console\Output\OutputInterface;

class Logger
{
    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $consoleOutput;

    /** @var bool */
    protected $enabled = false;

    /** @var bool */
    protected $verbose = false;

    public static function isEnabled(): bool
    {
        return app(MessageLogger::class)->enabled;
    }

    public function __construct(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;
    }

    public function enable($enabled = true)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function verbose($verbose = false)
    {
        $this->verbose = $verbose;

        return $this;
    }

    protected function info(string $message)
    {
        $this->line($message, 'info');
    }

    protected function warn(string $message)
    {
        $this->line($message, 'warning');
    }

    protected function error(string $message)
    {
        $this->line($message, 'error');
    }

    protected function line(string $message, string $style)
    {
        $styled = $style ? "<$style>$message</$style>" : $message;

        $this->consoleOutput->writeln($styled);
    }
}