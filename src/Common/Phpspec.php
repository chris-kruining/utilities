<?php

namespace CPB\Utilities\Common
{
    use PhpSpec\Console\Application;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Output\BufferedOutput;

    class Phpspec
    {
        private static function execute(array $args): string
        {
            $app = new Application('5.1.0');
            $input = new ArrayInput($args);
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);

            $app->setAutoExit(false);

            $exitcode = $app->run($input, $output);
            $result = $output->fetch();

            if($exitcode !== 0)
            {
                throw new \Exception($result);
            }

            return $result;
        }

        public static function run(string $conf = null): string
        {
            return static::execute([
                'run',
                '--format' => 'json',
                '--no-interaction' => true,
                '--verbose' => 2,
            ]);
        }
    }
}