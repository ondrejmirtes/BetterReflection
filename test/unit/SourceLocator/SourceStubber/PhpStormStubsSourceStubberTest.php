<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\SourceStubber;

use CompileError;
use Error;
use ParseError;
use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter as CoreReflectionParameter;
use ReflectionUnionType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function assert;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function in_array;
use function realpath;
use function sort;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @covers \Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
 */
class PhpStormStubsSourceStubberTest extends TestCase
{
    /** @var SourceStubber */
    private $sourceStubber;

    /** @var PhpInternalSourceLocator */
    private $phpInternalSourceLocator;

    /** @var ClassReflector */
    private $classReflector;

    /** @var FunctionReflector */
    private $functionReflector;

    /** @var ConstantReflector */
    private $constantReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $stubber = new PhpStormStubsSourceStubber($betterReflection->phpParser());
        if (PHP_VERSION_ID >= 80000) {
            $stubber = new AggregateSourceStubber(
                new ReflectionSourceStubber(),
                $stubber
            );
        }

        $this->sourceStubber            = $stubber;
        $this->phpInternalSourceLocator = new PhpInternalSourceLocator(
            $betterReflection->astLocator(),
            $this->sourceStubber
        );
        $this->classReflector           = new ClassReflector($this->phpInternalSourceLocator);
        $this->functionReflector        = new FunctionReflector($this->phpInternalSourceLocator, $this->classReflector);
        $this->constantReflector        = new ConstantReflector($this->phpInternalSourceLocator, $this->classReflector);
    }

    /**
     * @return string[][]
     */
    public function internalClassesProvider() : array
    {
        $classNames = array_merge(
            get_declared_classes(),
            get_declared_interfaces(),
            get_declared_traits()
        );

        return array_map(
            static function (string $className) : array {
                return [$className];
            },
            array_filter(
                $classNames,
                static function (string $className) : bool {
                    $reflection = new CoreReflectionClass($className);

                    if (! $reflection->isInternal()) {
                        return false;
                    }

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalClassesProvider
     */
    public function testInternalClasses(string $className) : void
    {
        $class = $this->classReflector->reflect($className);

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame($className, $class->getName());
        self::assertTrue($class->isInternal());
        self::assertFalse($class->isUserDefined());

        $internalReflection = new CoreReflectionClass($className);

        self::assertSame($internalReflection->isInterface(), $class->isInterface());
        self::assertSame($internalReflection->isTrait(), $class->isTrait());

        self::assertSameClassAttributes($internalReflection, $class);
    }

    private function assertSameParentClass(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalParentClass = $original->getParentClass();
        $stubbedParentClass  = $stubbed->getParentClass();

        self::assertSame(
            $originalParentClass ? $originalParentClass->getName() : null,
            $stubbedParentClass ? $stubbedParentClass->getName() : null
        );
    }

    private function assertSameInterfaces(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        $originalInterfacesNames = $original->getInterfaceNames();
        $stubbedInterfacesNames  = $stubbed->getInterfaceNames();

        sort($originalInterfacesNames);
        sort($stubbedInterfacesNames);

        self::assertSame($originalInterfacesNames, $stubbedInterfacesNames);
    }

    private function assertSameClassAttributes(CoreReflectionClass $original, ReflectionClass $stubbed) : void
    {
        self::assertSame($original->getName(), $stubbed->getName());

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && $original->getName() === 'ParseError') {
            return;
        }

        $this->assertSameParentClass($original, $stubbed);
        $this->assertSameInterfaces($original, $stubbed);

        foreach ($original->getMethods() as $method) {
            // Needs fix in JetBrains/phpstorm-stubs
            // Added in PHP 7.4.0
            if (PHP_VERSION_ID < 70400 && $method->getShortName() === '__unserialize') {
                continue;
            }

            // Fixed in PHP 7.4.6, needs fix in JetBrains/phpstorm-stubs
            if (PHP_VERSION_ID >= 70406 && $method->getShortName() === '__debugInfo') {
                continue;
            }

            $this->assertSameMethodAttributes($method, $stubbed->getMethod($method->getName()));
        }

        self::assertEquals($original->getConstants(), $stubbed->getConstants());
    }

    private function assertSameMethodAttributes(CoreReflectionMethod $original, ReflectionMethod $stubbed) : void
    {
        $originalParameterNames = array_map(
            static function (CoreReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $original->getParameters()
        );
        $stubParameterNames     = array_map(
            static function (ReflectionParameter $parameter) : string {
                return $parameter->getDeclaringFunction()->getName() . '.' . $parameter->getName();
            },
            $stubbed->getParameters()
        );

        // Needs fixes in JetBrains/phpstorm-stubs
        // self::assertSame($originalParameterNames, $stubParameterNames);

        foreach ($original->getParameters() as $parameter) {
            $stubbedParameter = $stubbed->getParameter($parameter->getName());

            if ($stubbedParameter === null) {
                // Needs fixes in JetBrains/phpstorm-stubs
                continue;
            }

            $this->assertSameParameterAttributes(
                $original,
                $parameter,
                $stubbedParameter
            );
        }

        self::assertSame($original->isPublic(), $stubbed->isPublic());
        self::assertSame($original->isPrivate(), $stubbed->isPrivate());
        self::assertSame($original->isProtected(), $stubbed->isProtected());
        self::assertSame($original->returnsReference(), $stubbed->returnsReference());
        self::assertSame($original->isStatic(), $stubbed->isStatic());
        self::assertSame($original->isFinal(), $stubbed->isFinal());
        $this->assertType($original->getReturnType(), $stubbed->getReturnType(), sprintf('Return type of %s::%s()', $original->getDeclaringClass()->getName(), $original->getName()));
    }

    private function assertSameParameterAttributes(
        CoreReflectionMethod $originalMethod,
        CoreReflectionParameter $original,
        ReflectionParameter $stubbed
    ) : void {
        $parameterName = $original->getDeclaringClass()->getName()
            . '#' . $originalMethod->getName()
            . '.' . $original->getName();

        self::assertSame($original->getName(), $stubbed->getName(), $parameterName);
        // Inconsistencies
        if (! in_array($parameterName, ['SplFileObject#fputcsv.fields', 'SplFixedArray#fromArray.array'], true)) {
            self::assertSame($original->isArray(), $stubbed->isArray(), $parameterName);
        }

        // Bugs in PHP: https://3v4l.org/RjCDr
        if (! in_array($parameterName, ['Closure#fromCallable.callable', 'CallbackFilterIterator#__construct.callback'], true)) {
            self::assertSame($original->isCallable(), $stubbed->isCallable(), $parameterName);
        }

        self::assertSame($original->canBePassedByValue(), $stubbed->canBePassedByValue(), $parameterName);
        // Bugs in PHP
        if (! in_array($parameterName, [
            'RecursiveIteratorIterator#getSubIterator.level',
            'RecursiveIteratorIterator#setMaxDepth.max_depth',
            'SplTempFileObject#__construct.max_memory',
            'MultipleIterator#__construct.flags',
        ], true)) {
            self::assertSame($original->isOptional(), $stubbed->isOptional(), $parameterName);
        }

        self::assertSame($original->isPassedByReference(), $stubbed->isPassedByReference(), $parameterName);
        self::assertSame($original->isVariadic(), $stubbed->isVariadic(), $parameterName);

        $class = $original->getClass();
        if ($class) {
            // Not possible to write "RecursiveIterator|IteratorAggregate" in PHP code in JetBrains/phpstorm-stubs
            if ($parameterName !== 'RecursiveTreeIterator#__construct.iterator') {
                $stubbedClass = $stubbed->getClass();

                self::assertInstanceOf(ReflectionClass::class, $stubbedClass, $parameterName);
                self::assertSame($class->getName(), $stubbedClass->getName(), $parameterName);
            }
        } else {
            // Bugs in PHP
            if (! in_array($parameterName, [
                'Error#__construct.previous',
                'Exception#__construct.previous',
                'Closure#bind.closure',
                'Generator#throw.exception',
            ], true)) {
                self::assertNull($stubbed->getClass(), $parameterName);
            }
        }

        $this->assertType($original->getType(), $stubbed->getType(), $parameterName);
    }

    /**
     * @return string[][]
     */
    public function internalFunctionsProvider() : array
    {
        $functionNames = get_defined_functions()['internal'];

        return array_map(
            static function (string $functionName) : array {
                return [$functionName];
            },
            array_filter(
                $functionNames,
                static function (string $functionName) : bool {
                    $reflection = new CoreReflectionFunction($functionName);

                    // Check only always enabled extensions
                    return in_array($reflection->getExtensionName(), ['Core', 'standard', 'pcre', 'SPL'], true);
                }
            )
        );
    }

    /**
     * @dataProvider internalFunctionsProvider
     */
    public function testInternalFunctions(string $functionName) : void
    {
        $stubbedReflection = $this->functionReflector->reflect($functionName);

        self::assertSame($functionName, $stubbedReflection->getName());
        self::assertTrue($stubbedReflection->isInternal());
        self::assertFalse($stubbedReflection->isUserDefined());

        $originalReflection = new CoreReflectionFunction($functionName);

        // Needs fixes in JetBrains/phpstorm-stubs or PHP
        if (in_array($functionName, [
            'setlocale',
            'trait_exists',
            'strtok',
            'strtr',
            'hrtime',
            'pack',
            'min',
            'max',
            'var_dump',
            'compact',
            'array_map',
            'array_intersect',
            'array_intersect_key',
            'array_intersect_ukey',
            'array_intersect_assoc',
            'array_uintersect',
            'array_uintersect_assoc',
            'array_intersect_uassoc',
            'array_uintersect_uassoc',
            'array_diff',
            'array_diff_key',
            'array_diff_ukey',
            'array_diff_assoc',
            'array_udiff',
            'array_udiff_assoc',
            'array_diff_uassoc',
            'array_udiff_uassoc',
            'array_multisort',
            'extract',
            'setcookie',
            'setrawcookie',
            'sapi_windows_vt100_support',
            'sapi_windows_cp_get',
            'stream_context_set_option',
            'debug_zval_dump',
        ], true)) {
            return;
        }

        // Changed in PHP 7.3.0
        if (PHP_VERSION_ID < 70300 && in_array($functionName, ['array_push', 'array_unshift'], true)) {
            return;
        }

        // Changed in PHP 7.4.0
        if (PHP_VERSION_ID < 70400 && in_array($functionName, [
            'pack',
            'array_merge',
            'array_merge_recursive',
            'preg_replace_callback',
            'preg_replace_callback_array',
        ], true)
        ) {
            return;
        }

        self::assertSame($originalReflection->getNumberOfParameters(), $stubbedReflection->getNumberOfParameters());
        self::assertSame($originalReflection->getNumberOfRequiredParameters(), $stubbedReflection->getNumberOfRequiredParameters());

        $stubbedReflectionParameters = $stubbedReflection->getParameters();
        foreach ($originalReflection->getParameters() as $parameterNo => $originalReflectionParameter) {
            $parameterName = sprintf('%s.%s', $functionName, $originalReflectionParameter->getName());

            $stubbedReflectionParameter = $stubbedReflectionParameters[$parameterNo];

            self::assertSame($originalReflectionParameter->isOptional(), $stubbedReflectionParameter->isOptional(), $parameterName);
            self::assertSame($originalReflectionParameter->isPassedByReference(), $stubbedReflectionParameter->isPassedByReference(), $parameterName);
            self::assertSame($originalReflectionParameter->canBePassedByValue(), $stubbedReflectionParameter->canBePassedByValue(), $parameterName);

            // Bugs in PHP
            if (! in_array($parameterName, ['preg_replace_callback.callback', 'header_register_callback.callback', 'sapi_windows_set_ctrl_handler.callable'], true)) {
                self::assertSame($originalReflectionParameter->isCallable(), $stubbedReflectionParameter->isCallable(), $parameterName);
            }

            self::assertSame($originalReflectionParameter->isVariadic(), $stubbedReflectionParameter->isVariadic(), $parameterName);

            $this->assertType($originalReflectionParameter->getType(), $stubbedReflectionParameter->getType(), $parameterName);
        }

        $this->assertType($originalReflection->getReturnType(), $stubbedReflection->getReturnType(), sprintf('Return type of %s()', $functionName));
    }

    private function assertType(?\ReflectionType $originalType, ?ReflectionType $stubbedType, string $message) : void
    {
        if (in_array($message, [
            'RecursiveTreeIterator#__construct.iterator',
            'SeekableIterator#seek.position',
            'sapi_windows_cp_set.code_page',
            'sapi_windows_cp_conv.subject',
            'WeakReference#create.referent',
            'Return type of WeakReference::create()',
            'Return type of WeakReference::get()',
        ], true)) {
            return;
        }

        if ($originalType instanceof ReflectionNamedType) {
            self::assertInstanceOf(\Roave\BetterReflection\Reflection\ReflectionNamedType::class, $stubbedType, $message);
            self::assertSame($originalType->getName(), $stubbedType->getName(), $message);
        } elseif ($originalType instanceof ReflectionUnionType) {
            self::assertInstanceOf(\Roave\BetterReflection\Reflection\ReflectionUnionType::class, $stubbedType);
            self::assertSame((string) $originalType, (string) $stubbedType, $message);
        } else {
            self::assertNull($originalType, $message);
        }
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function internalConstantsProvider() : array
    {
        $provider = [];

        /** @var array<string, array<string, int|string|float|bool|array|resource|null>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $extensionName => $extensionConstants) {
            // Check only always enabled extensions
            if (! in_array($extensionName, ['Core', 'standard', 'pcre', 'SPL'], true)) {
                continue;
            }

            foreach ($extensionConstants as $constantName => $constantValue) {
                // Not supported because of resource as value
                if (in_array($constantName, ['STDIN', 'STDOUT', 'STDERR'], true)) {
                    continue;
                }

                $provider[] = [$constantName, $constantValue, $extensionName];
            }
        }

        return $provider;
    }

    /**
     * @param mixed $constantValue
     *
     * @dataProvider internalConstantsProvider
     */
    public function testInternalConstants(string $constantName, $constantValue, string $extensionName) : void
    {
        $constantReflection = $this->constantReflector->reflect($constantName);

        self::assertInstanceOf(ReflectionConstant::class, $constantReflection);
        self::assertSame($constantName, $constantReflection->getName());
        self::assertSame($constantName, $constantReflection->getShortName());

        self::assertNotNull($constantReflection->getNamespaceName());
        self::assertFalse($constantReflection->inNamespace());
        self::assertTrue($constantReflection->isInternal());
        self::assertFalse($constantReflection->isUserDefined());
        // NAN cannot be compared
        if ($constantName === 'NAN') {
            return;
        }

        self::assertSame($constantValue, $constantReflection->getValue());
    }

    public function dataClassInNamespace() : array
    {
        return [
            ['http\\Client'],
            ['MongoDB\\Driver\\Manager'],
            ['Parle\\Stack'],
        ];
    }

    /**
     * @dataProvider dataClassInNamespace
     */
    public function testClassInNamespace(string $className) : void
    {
        $classReflection = $this->classReflector->reflect($className);

        $this->assertSame($className, $classReflection->getName());
    }

    public function dataFunctionInNamespace() : array
    {
        return [
            ['Couchbase\\basicDecoderV1'],
            ['MongoDB\\BSON\\fromJSON'],
            ['Sodium\\add'],
        ];
    }

    /**
     * @dataProvider dataFunctionInNamespace
     */
    public function testFunctionInNamespace(string $functionName) : void
    {
        $functionReflection = $this->functionReflector->reflect($functionName);

        $this->assertSame($functionName, $functionReflection->getName());
    }

    public function dataConstantInNamespace() : array
    {
        return [
            ['http\\Client\\Curl\\AUTH_ANY'],
            ['pcov\\all'],
            ['YAF\\ENVIRON'],
        ];
    }

    /**
     * @dataProvider dataConstantInNamespace
     */
    public function testConstantInNamespace(string $constantName) : void
    {
        $constantReflection = $this->constantReflector->reflect($constantName);

        $this->assertSame($constantName, $constantReflection->getName());
    }

    public function testNoStubForUnknownClass() : void
    {
        self::assertNull($this->sourceStubber->generateClassStub('SomeClass'));
    }

    public function testNoStubForUnknownFunction() : void
    {
        self::assertNull($this->sourceStubber->generateFunctionStub('someFunction'));
    }

    public function testNoStubForUnknownConstant() : void
    {
        self::assertNull($this->sourceStubber->generateConstantStub('SOME_CONSTANT'));
    }

    public function dataCaseInsensitiveClass() : array
    {
        return [
            [
                'SoapFault',
                'SoapFault',
            ],
            [
                'SOAPFault',
                'SoapFault',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveClass
     */
    public function testCaseInsensitiveClass(string $className, string $expectedClassName) : void
    {
        $classReflection = $this->classReflector->reflect($className);

        $this->assertSame($expectedClassName, $classReflection->getName());
    }

    public function dataCaseInsensitiveFunction() : array
    {
        return [
            [
                'htmlspecialchars',
                'htmlspecialchars',
            ],
            [
                'htmlSpecialChars',
                'htmlspecialchars',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveFunction
     */
    public function testCaseInsensitiveFunction(string $functionName, string $expectedFunctionName) : void
    {
        $functionReflection = $this->functionReflector->reflect($functionName);

        $this->assertSame($expectedFunctionName, $functionReflection->getName());
    }

    public function dataCaseInsensitiveConstant() : array
    {
        return [
            [
                'true',
                'TRUE',
            ],
            [
                '__file__',
                '__FILE__',
            ],
            [
                'YaF_VeRsIoN',
                'YAF_VERSION',
            ],
        ];
    }

    /**
     * @dataProvider dataCaseInsensitiveConstant
     */
    public function testCaseInsensitiveConstant(string $constantName, string $expectedConstantName) : void
    {
        $this->markTestSkipped();
        $constantReflector = $this->constantReflector->reflect($constantName);

        $this->assertSame($expectedConstantName, $constantReflector->getName());
    }

    public function dataCaseSensitiveConstant() : array
    {
        return [
            ['date_atom'],
            ['PHP_version_ID'],
            ['FiLeInFo_NoNe'],
        ];
    }

    /**
     * @dataProvider dataCaseSensitiveConstant
     */
    public function testCaseSensitiveConstant(string $constantName) : void
    {
        self::expectException(IdentifierNotFound::class);

        $this->constantReflector->reflect($constantName);
    }

    public function testFilename() : void
    {
        $reflection = $this->classReflector->reflect('XMLReader');
        if (PHP_VERSION_ID >= 80000) {
            self::assertNull($reflection->getFileName());

            return;
        }

        $this->assertSame(realpath(__DIR__ . '/../../../../vendor/jetbrains/phpstorm-stubs/xmlreader/xmlreader.php'), realpath($reflection->getFileName()));
    }

    public function dataMethodSinceVersion() : array
    {
        return [
            [
                'ReflectionProperty',
                'hasType',
                70400,
                true,
            ],
            [
                'ReflectionProperty',
                'hasType',
                70300,
                false,
            ],
            [
                'ReflectionProperty',
                'getType',
                70400,
                true,
            ],
            [
                'ReflectionProperty',
                'getType',
                70300,
                false,
            ],
            [
                'ReflectionProperty',
                'getType',
                80000,
                true,
            ],
            [
                'ReflectionClassConstant',
                'export',
                70400,
                true,
            ],
            [
                'ReflectionClassConstant',
                'export',
                80000,
                false,
            ],
            [
                'ReflectionFunction',
                'export',
                70400,
                true,
            ],
            [
                'ReflectionFunction',
                'export',
                80000,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataMethodSinceVersion
     */
    public function testMethodSinceVersion(
        string $className,
        string $methodName,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);
        self::assertSame($expectedExists, $classReflector->reflect($className)->hasMethod($methodName));
    }

    public function dataPropertySinceVersion() : array
    {
        return [];
    }

    /**
     * @dataProvider dataPropertySinceVersion
     */
    public function testPropertySinceVersion(
        string $className,
        string $propertyName,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);
        self::assertSame($expectedExists, $classReflector->reflect($className)->hasProperty($propertyName));
    }

    public function dataClassConstantSinceVersion() : array
    {
        return [];
    }

    /**
     * @dataProvider dataClassConstantSinceVersion
     */
    public function testClassConstantSinceVersion(
        string $className,
        string $constantName,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);
        self::assertSame($expectedExists, $classReflector->reflect($className)->hasConstant($constantName));
    }

    public function dataClassSinceVersion() : array
    {
        return [
            [
                ReflectionNamedType::class,
                70000,
                false,
            ],
            [
                ReflectionNamedType::class,
                70100,
                true,
            ],
            [
                ReflectionNamedType::class,
                70200,
                true,
            ],
            [
                'CompileError',
                70300,
                true,
            ],
            [
                'CompileError',
                70000,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataClassSinceVersion
     */
    public function testClassSinceVersion(
        string $className,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);

        try {
            $reflection = $classReflector->reflect($className);
            if (! $expectedExists) {
                $this->fail(sprintf('Class %s should not exist.', $className));
            }

            self::assertSame($className, $reflection->getName());
        } catch (IdentifierNotFound $e) {
            if ($expectedExists) {
                $this->fail(sprintf('Class %s should exist.', $className));
            }

            self::assertSame($className, $e->getIdentifier()->getName());
        }
    }

    public function dataFunctionSinceVersion() : array
    {
        return [
            [
                'password_algos',
                70400,
                true,
            ],
            [
                'password_algos',
                70300,
                false,
            ],
            [
                'setcookie',
                70100,
                true,
            ],
            [
                'array_push',
                70100,
                true,
            ],
            [
                'array_key_first',
                70300,
                true,
            ],
            [
                'array_key_first',
                70200,
                false,
            ],
            [
                'mcrypt_ecb',
                50600,
                true,
            ],
            [
                'mcrypt_ecb',
                70000,
                false,
            ],
            [
                'mcrypt_ecb',
                70100,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataFunctionSinceVersion
     */
    public function testFunctionSinceVersion(
        string $functionName,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [,$functionReflector] = $this->getReflectors($phpVersionId);

        try {
            $reflection = $functionReflector->reflect($functionName);
            if (! $expectedExists) {
                $this->fail(sprintf('Function %s should not exist.', $functionName));
            }

            self::assertSame($functionName, $reflection->getName());
        } catch (IdentifierNotFound $e) {
            if ($expectedExists) {
                $this->fail(sprintf('Function %s should exist.', $functionName));
            }

            self::assertSame($functionName, $e->getIdentifier()->getName());
        }
    }

    public function dataConstantSinceVersion() : array
    {
        return [
            [
                'PHP_OS_FAMILY',
                70200,
                true,
            ],
            [
                'PHP_OS_FAMILY',
                70100,
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataConstantSinceVersion
     */
    public function testConstantSinceVersion(
        string $constantName,
        int $phpVersionId,
        bool $expectedExists
    ) : void {
        [,,$constantReflector] = $this->getReflectors($phpVersionId);

        try {
            $reflection = $constantReflector->reflect($constantName);
            if (! $expectedExists) {
                $this->fail(sprintf('Constant %s should not exist.', $constantName));
            }

            self::assertSame($constantName, $reflection->getName());
        } catch (IdentifierNotFound $e) {
            if ($expectedExists) {
                $this->fail(sprintf('Constant %s should exist.', $constantName));
            }

            self::assertSame($constantName, $e->getIdentifier()->getName());
        }
    }

    public function dataSubclass() : array
    {
        return [
            [
                ParseError::class,
                CompileError::class,
                70300,
            ],
            [
                ParseError::class,
                CompileError::class,
                70400,
            ],
            [
                ParseError::class,
                Error::class,
                70200,
            ],
        ];
    }

    /**
     * @dataProvider dataSubclass
     */
    public function testSubclass(
        string $className,
        string $subclassName,
        int $phpVersionId
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);
        $reflection       = $classReflector->reflect($className);
        self::assertTrue($reflection->isSubclassOf($subclassName));
    }

    public function dataImmediateInterfaces() : array
    {
        return [
            [
                'PDOStatement',
                ['Traversable'],
                70400,
            ],
            [
                'PDOStatement',
                ['IteratorAggregate'],
                80000,
            ],
            [
                'SplFixedArray',
                ['Iterator', 'ArrayAccess', 'Countable'],
                70400,
            ],
            [
                'SplFixedArray',
                ['Iterator', 'ArrayAccess', 'Countable', 'IteratorAggregate'],
                80000,
            ],
        ];
    }

    /**
     * @param string[] $interfaceNames
     *
     * @dataProvider dataImmediateInterfaces
     */
    public function testImmediateInterfaces(
        string $className,
        array $interfaceNames,
        int $phpVersionId
    ) : void {
        [$classReflector] = $this->getReflectors($phpVersionId);
        assert($classReflector instanceof ClassReflector);
        $reflection = $classReflector->reflect($className);
        self::assertSame($interfaceNames, array_keys($reflection->getImmediateInterfaces()));
    }

    public function dataIsPresentClass() : array
    {
        return [
            [
                'DOMImplementationList',
                80000,
                false,
            ],
            [
                'DOMImplementationList',
                70400,
                true,
            ],
            [
                'ReflectionClass',
                70100,
                true,
            ],
            [
                'AbcAbc',
                70100,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataIsPresentClass
     */
    public function testIsPresentClass(string $className, int $phpVersionId, ?bool $expected) : void
    {
        $parser        = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]));
        $sourceStubber = new PhpStormStubsSourceStubber($parser, $phpVersionId);
        self::assertSame($expected, $sourceStubber->isPresentClass($className));
    }

    public function dataIsPresentFunction() : array
    {
        return [
            [
                'money_format',
                70400,
                true,
            ],
            [
                'money_format',
                80000,
                false,
            ],
            [
                'htmlspecialchars',
                70400,
                true,
            ],
            [
                'blabla',
                70400,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataIsPresentFunction
     */
    public function testIsPresentFunction(string $functionName, int $phpVersionId, ?bool $expected) : void
    {
        $parser        = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]));
        $sourceStubber = new PhpStormStubsSourceStubber($parser, $phpVersionId);
        self::assertSame($expected, $sourceStubber->isPresentFunction($functionName));
    }

    /**
     * @return array{ClassReflector, FunctionReflector, ConstantReflector}
     */
    private function getReflectors(int $phpVersionId) : array
    {
        // memoizing parser screws things up so we need to create the universe from the start
        $parser                   = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]));
        $functionReflector        = null;
        $astLocator               = new Locator($parser, static function () use (&$functionReflector) : FunctionReflector {
            return $functionReflector;
        });
        $sourceStubber            = new PhpStormStubsSourceStubber($parser, $phpVersionId);
        $phpInternalSourceLocator = new PhpInternalSourceLocator(
            $astLocator,
            $sourceStubber
        );
        $classReflector           = new ClassReflector($phpInternalSourceLocator);
        $functionReflector        = new FunctionReflector($phpInternalSourceLocator, $classReflector);
        $constantReflector        = new ConstantReflector($phpInternalSourceLocator, $classReflector);

        return [$classReflector, $functionReflector, $constantReflector];
    }
}
