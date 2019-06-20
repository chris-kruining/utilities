<?php

namespace CPB\Utilities\Common
{
    use Composer\Console\Application;
    use CPB\Utilities\Collections\Collection;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Output\BufferedOutput;

    final class Composer
    {
        private static function execute(array $args): string
        {
            $app = new Application();
            $input = new ArrayInput($args);
            $output = new BufferedOutput(
                BufferedOutput::VERBOSITY_NORMAL,
                false
            );

            $app->setAutoExit(false);

            $exitcode = $app->run($input, $output);
            $result = $output->fetch();

            if($exitcode !== 0)
            {
                throw new \Exception($result);
            }

            return $result;
        }

        public static function outdated(): iterable
        {
            $res = static::execute([
                'show',
                '--latest' => true,
                '--outdated' => true,
            ]);

            $match = Regex::matchAll(
                '/(?<package>[a-zA-Z0-9_\/-]+) +(?<old>v?\d\.\d\.\d+).+?(?<new>v?\d\.\d\.\d+)\S* *(?<description>.*?)$/m',
                $res,
                PREG_SET_ORDER
            );

            return Collection::from($match)
                ->map(function($k, $match){
                    return $match->intersectKey(Collection::from([
                        'package',
                        'old',
                        'new',
                        'description'
                    ])->flip());
                });
        }
    }
}