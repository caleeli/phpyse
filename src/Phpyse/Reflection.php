<?php

namespace Phpyse;

use ReplaceCode\CodeParser;

/**
 * Description of Reflection
 *
 * @author davidcallizaya
 */
class Reflection
{

    public function findUsages($path)
    {
        $this->glob($path);
    }

    private function glob($path)
    {
        foreach(glob("$path/*.php") as $filename) {
            $source = file_get_contents($filename);
            $finder = new CodeParser($source);
            $aliases = [];
            $finder->forEachMatches([
                T_USE,
                T_WHITESPACE,
                [
                    'name' => 'className',
                    [
                        T_STRING,
                        'multiplicity' => '?',
                    ],
                    [
                        '\\',
                        T_STRING,
                        'multiplicity' => '*',
                    ],
                ],
                [
                    'multiplicity' => '?',
                    T_WHITESPACE,
                    T_AS,
                    T_WHITESPACE,
                    [
                        'name' => 'alias',
                        T_STRING,
                    ],
                ],
                ';'
            ], function ($cc, $parser) use (&$aliases) {
                $className = $parser->tokens2string($cc['className']);
                $classParts = explode('\\', $className);
                $alias = isset($cc['alias'])
                        ? $parser->tokens2string($cc['alias'])
                        : $classParts[count($classParts) - 1];
                $aliases[$alias] = substr($className, 0, 1) === '\\'
                        ? $className
                        : '\\' . $className;
            });
            $newInstances = [];
            $finder->forEachMatches([
                [
                    'name' => 'variable',
                    T_VARIABLE,
                ],
                [
                    T_WHITESPACE,
                    'multiplicity' => '?',
                ],
                '=',
                [
                    T_WHITESPACE,
                    'multiplicity' => '?',
                ],
                'new',
                T_WHITESPACE,
                [
                    'name' => 'className',
                    [
                        T_STRING,
                        'multiplicity' => '?',
                    ],
                    [
                        '\\',
                        T_STRING,
                        'multiplicity' => '*',
                    ],
                ],
            ], function ($cc, $parser) use ($aliases, &$newInstances) {
                $variable = $parser->tokens2string($cc['variable']);
                $className = $parser->tokens2string($cc['className']);
                $class = isset($aliases[$className])
                    ? $aliases[$className]
                    : $className;
                $class = substr($class, 0, 1) === '\\' ? $class : '\\' . $class;
                $newInstances[$variable] = $class;
            });
            dump($filename);
            $finder->forEachMatches([
                [
                    'name' => 'variable',
                    T_VARIABLE,
                ],
                [
                    T_WHITESPACE,
                    'multiplicity' => '?',
                ],
                '->',
                [
                    T_WHITESPACE,
                    'multiplicity' => '?',
                ],
                [
                    'name' => 'method',
                    T_STRING,
                ],
                [
                    T_WHITESPACE,
                    'multiplicity' => '?',
                ],
                '(',
            ], function ($cc, $parser) use ($aliases, &$newInstances) {
                $variable = $parser->tokens2string($cc['variable']);
                $method = $parser->tokens2string($cc['method']);
                $class = isset($newInstances[$variable])
                    ? '('.$newInstances[$variable].')'
                    : $variable;
                dump($class . "->$method()");
            });
        }
    }
}
