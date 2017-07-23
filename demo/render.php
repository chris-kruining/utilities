<?php

require_once __DIR__ . '/../vendor/autoload.php';

class Renderer
{
    protected $arguments;
    protected $class;
    protected $methods;

    public function __construct(string $class, ...$constructorArguments)
    {
        $this->class = $class;
        $this->argument = $constructorArguments;
    }

    public function __toString() : string
    {
        $path = '/' . pathinfo(dirname(__DIR__))['basename'] . '/' . pathinfo(__DIR__)['basename'] . '/style.css';
        $methods = [];

        foreach($this->methods as $method => $parameters)
        {
            $methods[] = is_numeric($method)
                ? $this->DrawMethodChain($parameters)
                : $this->DrawMethod($method, ...$parameters);
        }

        $buffer = '
            <html>
                <head>
                    <title>Demo for ' . $this->class . '</title>
                
                    <link rel="stylesheet" href="' . $path . '">
                </head>
                
                <body>
                    <h1>Demo for ' . $this->class . '</h1>
                    
                    ' . join('', $methods) . '
                </body>
            </html>
        ';

        return $buffer;
    }

    public function DrawMethod(string $method, ...$parameters): string
    {
        $class = $this->class;
        $inst = new $class(...$this->argument);

        $buffer = '<card><h3>' . $class . '::' . $method . '</h3>';

        $buffer .= 'Parmeters :: ' . json_encode($parameters, JSON_PRETTY_PRINT) . '<br />';
        $buffer .= 'Result    :: ' . json_encode($inst->$method(...$parameters), JSON_PRETTY_PRINT) . '<br />';

        $buffer .= '</card>';

        return $buffer;
    }

    public function DrawMethodChain(array $chain): string
    {
        var_dump($chain);
        phpinfo();
        die;

        $class = $this->class;
        $inst = new $class(...$this->argument);

        $buffer = '<card><h3>' . $class . '::' . $method . '</h3>';

        $buffer .= 'Parmeters :: ' . json_encode($parameters, JSON_PRETTY_PRINT) . '<br />';
        $buffer .= 'Result    :: ' . json_encode($inst->$method(...$parameters), JSON_PRETTY_PRINT) . '<br />';

        $buffer .= '</card>';

        return $buffer;
    }

    public static function Init(string $class, array $methods, ...$constructorArguments) : Renderer
    {
        $inst = new static($class, ...$constructorArguments);
        $inst->methods = $methods;

        return $inst;
    }
}

