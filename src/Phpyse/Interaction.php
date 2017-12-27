<?php

namespace Phpyse;

class Interaction
{
    private static $previous = [];
    private static $packages = [
        'app'           => [
            'app',
        ],
        'Controller'    => [
            'Maveriks\\WebApplication',
            'Luracast\\Restler\\Restler',
            'Maveriks\\Extension\\Restler',
            /* ],
              'OAuth'      => [ */
            'ProcessMaker\\Services\\OAuth2\\Server',
            'OAuth2\\Server',
            'OAuth2\\Controller\\TokenController',
            'OAuth2\\GrantType\\UserCredentials',
            'ProcessMaker\\Services\\OAuth2\\PmPdo',
        ],
        'AccessControl' => [
            'RBAC',
            'RbacUsers',
            'BaseRbacUsersPeer',
            'BaseUsersRolesPeer',
            'ProcessMaker\\Policies\\AccessControl',
            //'ProcessMaker\\BusinessModel\\User',
            /* ],
              'License'    => [ */
            'PMLicensedFeatures',
            'PmLicenseManager',
        ],
        'DB'            => [
            'BasePeer',
        ],
    ];

    public static function trace($trace)
    {
        $source = 'app';
        $i = 0;
        $prev = [];
        while ($call = array_pop($trace)) {
            $target = static::logCall($source, $call, $i, $prev);
            $source = $target;
            $i++;
        }
        static::$previous = $prev;
    }

    private static function logCall($source, $call, $i, &$prev)
    {
        $isGlobal = !isset($call["class"]);
        $target = $isGlobal ? $source : $call["class"];
        $target0 = $isGlobal ? '' : $target;
        if (!$isGlobal) {
            $refClass = new \ReflectionClass($target);
            $isModel = strpos($refClass->getFileName(), 'engine/classes/model/')
                !== false;
        } else {
            $isModel = false;
        }
        $classified = false;
        foreach (static::$packages as $package => $elements) {
            if (array_search($target, $elements) !== false) {
                $target = $package;
                $classified = true;
                break;
            }
        }
        if (!$isGlobal && !$classified && $isModel) {
            $target = 'Model';
        } elseif (!$isGlobal && !$classified) {
            $target = 'Business';
        }
        $function = @$call['line'] . "| " . $target0 . "@" . $call['function'];
        $prev[$i] = $target . '->' . $function;
        if (!isset(static::$previous[$i]) || static::$previous[$i] !== $prev[$i]) {
            error_log("{$source}" . ($isGlobal ? '-->>' : '->') . "{$target}:$function"
                . ($target==='Business'?static::getParams($target0, $call['function']):'()'));
        }
        return $target;
    }

    private static function getParams($class, $method)
    {
        if (!$class) return '()';
        /* @var $param \ReflectionParameter */
        $formated = [];
        $refMethod = new \ReflectionMethod($class, $method);
        foreach($refMethod->getParameters() as $param) {
            $formated[] = $param->getName();
        }
        return '('.implode(",", $formated).')';
    }

    private static function formatParams($params)
    {
        $formated = [];
        foreach ($params as $p) {
            if (is_object($p)) $formated[] = '{}';
            elseif (is_array($p)) $formated[] = '[]';
            else $formated[] = json_encode($p);
        }
        return '('.implode(",", $formated).')';
    }
}
