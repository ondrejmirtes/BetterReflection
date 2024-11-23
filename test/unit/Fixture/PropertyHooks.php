<?php

// https://wiki.php.net/rfc/property-hooks

namespace Roave\BetterReflectionTest\Fixture;

use function strtolower;

class PropertyHooks
{
    public string $readOnlyHook {
        get {
            return 'hook';
        }
    }

    public string $writeOnlyHook {
        set (string $value) {
            $this->writeOnlyHook = $value;
        }
    }

    public string $readAndWriteHook {
        get {
            $this->readAndWriteHook;
        }
        set (string $value) {
            $this->readAndWriteHook = $value;
        }
    }

    public string $virtualBecauseOfStrangeSetHook {
        set (string $value) {
            $this->differentProperty = $value;
        }
    }

    public string $shortSyntaxHook {
        set => strtolower($value);
    }
}

abstract class AbstractPropertyHooks
{
    abstract public string $hook { get; }
}

class GetPropertyHook extends AbstractPropertyHooks
{
    public string $hook {
        get {
            return 'hook';
        }
    }
}

class GetAndSetPropertyHook extends GetPropertyHook
{
    public string $hook {
        set (string $value) {
            $this->hook = $value;
        }
    }
}

trait PropertyHookTrait
{
    public string $hook {
        get {
            return 'hook';
        }
    }
}

class UsePropertyHookFromTrait
{
    use PropertyHookTrait;
}
