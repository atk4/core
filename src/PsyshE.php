<?php

namespace atk4\core;

/*
 * Extend Psysh to display ATK Exceptions nicely by adding into
 * ~/.config/psysh/config.php
 *
 * return [ 'commands' => [ new \atk4\core\PsyshE  ] ];
 *
 * If this file fails to load, add this line above return:
 *
 * include '/path/to/atk4/core/PsyshE.php';
 *
 * If Agile Exception is thrown, you can get a nice output
 * by typing
 *
 * >>> e
 *
 */

use Psy\Command\TraceCommand;
use Psy\Context;
use Psy\ContextAware;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsyshE extends TraceCommand implements ContextAware
{
    protected $context;

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    protected function configure()
    {
        $this
            ->setName('e')
            ->setDescription('Nicely display Agile Exceptions')
            ->setHelp(
                <<<'HELP'
Display ATK exceptions nicely
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exception = $this->context->getLastException();
        if (!$exception instanceof \atk4\core\Exception) {
            throw new \InvalidArgumentException('Last exception wasn\'t Agile Exception. use wtf');
        }
        $output->write($exception->getColorfulText());
        /*
        $count     = $input->getOption('verbose') ? PHP_INT_MAX : pow(2, max(0, (strlen($incredulity) - 1)));

        $trace     = $this->getBacktrace($exception, $count);
        $shell = $this->getApplication();
        $output->page(function ($output) use ($exception, $trace, $shell) {
            $shell->renderException($exception, $output);
            $output->writeln('--');
            $output->write($trace, true, ShellOutput::NUMBER_LINES);
        });
         */
    }
}
