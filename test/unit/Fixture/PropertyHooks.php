<?php

// https://wiki.php.net/rfc/property-hooks

namespace Roave\BetterReflectionTest\Fixture;

use function strtolower;
use function strtoupper;

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
}

abstract class ToBeVirtualOrNotToBeVirtualThatIsTheQuestion
{
    public string $notVirtualBecauseNoHooks = 'string';

    public string $notVirtualBecauseOfPublicVisibilityAndThePropertyIsUsedInGet {
        get {
            return strtoupper($this->notVirtualBecauseOfPublicVisibilityAndThePropertyIsUsedInGet);
        }
    }

    protected string $virtualBecauseOfNotPublicVisibilityAndNoSet {
        get {
            return strtoupper($this->virtualBecauseOfNotPublicVisibilityAndNoSet);
        }
    }

    public string $notVirtualBecauseOfShortSyntax {
        set => strtolower($value);
    }

    public string $virtualBecauseThePropertyIsNotUsedInGet {
        get => 'value';
    }

    public string $virtualBecauseSetWorksWithDifferentProperty {
        set (string $value) {
            $this->differentProperty = $value;
        }
    }

    public string $notVirtualBecauseIsPublicSoTheSetWithDifferentPropertyIsNotRelevant {
        set {
            $this->differentProperty = $value;
        }
        get {
            return $this->notVirtualBecauseIsPublicSoTheSetWithDifferentPropertyIsNotRelevant;
        }
    }

    abstract public string $virtualBecauseGetAndSetAbstract {
        get;
        set;
    }

    abstract public string $notVirtualBecauseSetIsNotAbstract {
        get;
        set (string $value) {
            $this->notVirtualBecauseSetIsNotAbstract = $value;
        }
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

interface InterfacePropertyHooks
{
    public string $readOnlyHook { get; }

    public string $writeOnlyHook { set; }

    public string $readAndWriteHook { get; set; }
}
