<?php


namespace Niirrty\Reflection;


class ClassReflector
{


    #region // P R I V A T E   F I E L D S


    /**
     * Stores the full qualified class name, including the namespace.
     *
     * @var string
     */
    private $classNameFull;

    /**
     * The class name without a namespace.
     *
     * @var string
     */
    private $className;

    /**
     * The namespace (without the leading \)
     *
     * @var string
     */
    private $ns;

    /**
     * Defines if the class is marked as final.
     *
     * @var bool
     */
    private $isFinal;

    /**
     * Defines if the class is abstract.
     *
     * @var bool
     */
    private $isAbstract;

    /**
     * Defines if its not a class but a interface.
     *
     * @var bool
     */
    private $isInterface;

    /**
     * Defines if its not a class but a trait
     *
     * @var bool
     */
    private $isTrait;

    /**
     * Defines the class comment, or NULL.
     *
     * @var string|null
     */
    private $comment;

    /**
     * A numeric array with the names of all implemented interfaces.
     *
     * @var array
     */
    private $implements;

    /**
     * A numeric array with the names of all extended classes/interfaces.
     *
     * @var array
     */
    private $extends;

    /**
     * Info about all defined class constants (numeric indicated array)
     *
     * Each constant is defined by a array with the following keys:
     *
     * - 'name' : The constant name
     * - 'comment': PHP Doc comment of the constant, or NULL if no comment exists
     * - 'modifier': The modifier 'public', 'protected', 'private' or NULL if no modifier is defined
     * - 'value': The value, associated with the constant
     * - 'type': The type of the constant value.
     *
     * @var array
     */
    private $constants;

    /**
     * The tabulator string for 1 incrementing level.
     *
     * @var string
     */
    private $tab = '    ';

    /**
     * All traits, depending to the class.
     *
     * @var array
     */
    private $uses;

    /**
     * Info about all defined none static class vars/properties (numeric indicated array)
     *
     * Each var is defined by a array with the following keys:
     *
     * - 'name' : The var name
     * - 'comment': PHP Doc comment of the var, or NULL if no comment exists
     * - 'modifier': The modifier 'public', 'protected', 'private' or NULL if no modifier is defined
     * - 'value': The value, associated with the var, OR NULL if no value is defined
     * - 'type': The type of the var value or NULL.
     *
     * @var array
     */
    private $vars;

    /**
     *
     * Info about all defined static class vars/properties (numeric indicated array)
     *
     * Each var is defined by a array with the following keys:
     *
     * - 'name' : The var name
     * - 'comment': PHP Doc comment of the var, or NULL if no comment exists
     * - 'modifier': The modifier 'public', 'protected', 'private' or NULL if no modifier is defined
     * - 'value': The value, associated with the var, OR NULL if no value is defined
     * - 'type': The type of the var value or NULL.
     *
     * @var array
     */
    private $varsStatic;

    /**
     * Info about all class methods.
     *
     * Format is:
     *
     * <code>
     *
     * </code>
     *
     * @var array
     */
    private $methods;
    /** @var int */
    private $methodCount;
    /** @var array */
    private $methodsStatic;
    /** @var bool */
    private $prettyPrint;

    private const PL = '~…………%/A^°²³¯¯³²°^A\\%…………~';

    #endregion


    #region // P R I V A T E   I N T E R N A L   F I E L D S

    /** @var \ReflectionClass */
    private $_class;
    /** @var string */
    private $_inc;
    /** @var bool */
    private $_parsed;

    #endregion


    #region // C O N S T R U C T O R

    /**
     * ClassReflector constructor.
     *
     * @param string $classNameFull
     * @param bool   $prettyPrint
     *
     * @throws \ReflectionException
     */
    public function __construct( string $classNameFull, bool $prettyPrint = true )
    {

        // Remember initial parameters
        $this->classNameFull = $classNameFull;
        $this->prettyPrint = $prettyPrint;

        // Get class name without namespace
        $tmp = \explode( '\\', $classNameFull );
        $this->className = $tmp[ \count( $tmp ) - 1 ];

        // Init the ReflectionClass instance for current defined class
        $this->_class = new \ReflectionClass( $this->classNameFull );

        // Get the Namespace of the class
        $this->ns = $this->_class->getNamespaceName();

        // Get all modifier information
        $this->isFinal     = $this->_class->isFinal();
        $this->isAbstract  = $this->_class->isAbstract();
        $this->isInterface = $this->_class->isInterface();
        $this->isTrait     = $this->_class->isTrait();

        // Get the comment if defined, NULL otherwise
        $this->comment = $this->_class->getDocComment();
        if ( empty( $this->comment ) ) { $this->comment = null; }

        // Init the 6 required array fields

        // Numeric indicated
        $this->extends       = [];
        // Numeric indicated
        $this->implements    = [];
        // Keys are the constant names, values are arrays with the keys 'comment', 'modifier', 'value', 'type'
        $this->constants     = [];
        //
        $this->uses          = [];
        // Keys are the variable names, values are arrays with the keys 'comment', 'modifier', 'value', 'type'
        $this->vars          = [];
        // Keys are the variable names, values are arrays with the keys 'comment', 'modifier', 'value', 'type'
        $this->varsStatic    = [];
        //
        $this->methods       = [];
        //
        $this->methodsStatic = [];

        $this->_parsed = false;

    }

    #endregion


    #region // P U B L I C   M E T H O D S

    /**
     * Extracts all data from the class.
     */
    public function parse()
    {

        $this->_parsed = false;

        // Find the Names of all depending interfaces
        $this->extractInterfaceData();

        // Handle a maybe extended parent class
        $this->extractParentClassData();

        // Find info about defined class constants
        $this->extractConstantsData();

        $this->extractVarsData();

        $this->extractMethodsData();

        $this->_parsed = true;

    }

    /**
     * Generates the PHP signature of the whole class.
     *
     * @param string|null $tabChars The chars, representing a single tab increment
     *
     * @return string Returns the generated PHP Code
     */
    public function generatePHPSignatures( ?string $tabChars = null ) : string
    {

        // Parse data for all class members, if not already done
        if ( ! $this->_parsed )
        {
            $this->parse();
        }

        // Use the parameter if defined
        if ( null !== $tabChars && '' !== $tabChars )
        {
            $this->tab = $tabChars;
        }

        // reset the main increment chars
        $this->_inc = '';

        // Start with the namespace code
        $php  = $this->generateNameSpaceStart();

        // now generate the class comment code
        $php .= $this->generateComment( $this->comment );

        // Generate the signature of the class/trait/interface including the open curly bracket '{'
        $php .= $this->generateClassSignature();

        // Generate the code for all class constant definitions
        $php .= $this->generateConstants();

        // Generate the code for all class vars/properties
        $php .= $this->generateVars();

        $php .= $this->generateMethods();


        // Code for closing open class and namespace

        $hasNs = ! empty( $this->ns );

        // End of the class
        $this->_inc = $hasNs ? $this->tab : '';
        $php .= PHP_EOL . ( $this->prettyPrint ? PHP_EOL : '' ) . $this->_inc . '}';

        // End of the namespace
        if ( $hasNs )
        {
            $this->_inc = '';
            $php .= PHP_EOL . ( $this->prettyPrint ? PHP_EOL : '' ) . $this->_inc . '}' . PHP_EOL . PHP_EOL;
        }

        return $php;

    }

    #endregion


    #region // P R I V A T E   M E T H O D S

    /**
     * Generate the namespace definition PHP code if a namespace is defined.
     *
     * @return string
     */
    private function generateNameSpaceStart() : string
    {

        if ( ! empty( $this->ns ) )
        {

            $this->_inc = $this->tab;

            return 'namespace '
                   . $this->ns
                   . ( $this->prettyPrint
                    ? ( PHP_EOL . '{' . PHP_EOL . PHP_EOL )
                    : ( ' {' . PHP_EOL ) );

        }

        return '';

    }

    /**
     * Generate the comment PHP code if a comment is defined.
     *
     * @param string|null $comment
     *
     * @return string
     */
    private function generateComment( ?string $comment ) : string
    {

        if ( null !== $comment )
        {
            return \preg_replace(
                '~(\r\n|\n)\s*\\*~',
                PHP_EOL . $this->_inc . ' *',
                $this->prettyPrint
                    ? $comment
                    : \preg_replace( '~(\r\n|\n)\\s+\\*\\s*(\r\n|\n)~', PHP_EOL, $comment )
            );
        }

        return '';

    }

    /**
     * Generate the class/trait/interface PHP code class signature.
     *
     * @return string
     */
    private function generateClassSignature() : string
    {

        if ( $this->isInterface )
        {
            $php = PHP_EOL . 'interface ' . $this->className;
        }
        else if ( $this->isTrait )
        {
            $php = PHP_EOL . 'trait ' . $this->className;
        }
        else
        {
            $php = PHP_EOL . ($this->isAbstract ? 'abstract ' : '') . ($this->isFinal ? 'final ' : '') . 'class ' . $this->className;
        }

        if ( 0 < \count( $this->extends ) )
        {
            $php .= ' extends' . ( $this->prettyPrint ? ( PHP_EOL . $this->_inc . $this->tab ) : ' ' );
            $php .= \implode( ',' . ( $this->prettyPrint ? ( PHP_EOL . $this->_inc . $this->tab ) : ' ' ), $this->extends );
        }

        if ( 0 < \count( $this->implements ) )
        {
            $php .= ' implements' . ( $this->prettyPrint ? ( PHP_EOL . $this->_inc . $this->tab ) : ' ' );
            $php .= \implode( ',' . ( $this->prettyPrint ? ( PHP_EOL . $this->_inc . $this->tab ) : ' ' ), $this->implements );
        }

        $php .= ( $this->prettyPrint ? PHP_EOL . $this->_inc . '{' . PHP_EOL : ' {' );

        $this->_inc .= $this->tab;

        return $php;

    }

    /**
     * Generate the PHP code for all constants if constants are defined.
     *
     * @return string
     */
    private function generateConstants() : string
    {

        if ( 1 > \count( $this->constants ) )
        {
            return '';
        }

        $php = PHP_EOL . ( $this->prettyPrint ? PHP_EOL : '' );

        if ( $this->prettyPrint ) { $php .= $this->_inc . '#region // C L A S S   C O N S T A N T S' . PHP_EOL; }

        foreach ( $this->constants as $constData )
        {

            $php .= PHP_EOL . $this->_inc . $this->generateComment( $constData[ 'comment' ] );
            $php .= PHP_EOL . $this->_inc;

            if ( ! empty( $constData[ 'modifier' ] ) )
            {
                $php .= \strtolower( $constData[ 'modifier' ] ) . ' ';
            }

            $php .= 'const ' . $constData[ 'name' ] . ' = ' . $this->valueToPHPCode( $constData[ 'value' ], $this->_inc ) . ';';

        }

        if ( $this->prettyPrint ) { $php .= PHP_EOL . PHP_EOL . $this->_inc . '#endregion' . PHP_EOL; }
        else { $php .= PHP_EOL; }

        return $php;

    }

    /**
     * Generate the PHP code for all class variables.
     *
     * @return string
     */
    private function generateVars() : string
    {
        return $this->_generateVars( $this->vars, false ) . $this->_generateVars( $this->varsStatic, true );
    }

    /**
     * Generate the PHP code for all class methods.
     *
     * @return string
     */
    private function generateMethods() : string
    {

        $php = '';
        if ( $this->methodCount < 1 )
        {
            return $php;
        }

        foreach ( $this->methods as $mType => $methodsWithAccessors )
        {
            // $mType can be 'instance' or 'static'

            if ( 'static' === $mType ) { $mTypeStr = 'static '; }
            else { $mTypeStr = ''; }

            foreach ( $methodsWithAccessors as $accessor => $methods )
            {
                // $accessor can be 'public', 'protected', 'private' or 'none'

                if ( $this->prettyPrint && 0 < \count( $methods ) )
                {
                    $php .= PHP_EOL . PHP_EOL . $this->_inc . '#region // ';
                    switch ( $accessor )
                    {
                        case 'public':
                            $php .= 'P U B L I C   ';
                            break;
                        case 'protected':
                            $php .= 'P R O T E C T E D   ';
                            break;
                        case 'private':
                            $php .= 'P R I V A T E   ';
                            break;
                        default:
                            break;
                    }
                    if ( 'static' === $mType )
                    {
                        $php .= 'S T A T I C   ';
                    }
                    $php .= 'M E T H O D S';
                }

                foreach ( $methods as $method )
                {

                    $php .= PHP_EOL . ( $this->prettyPrint ? PHP_EOL : '' ) . $this->_inc;

                    // Write method comment, if defined
                    if ( ! empty( $method[ 'comment' ] ) )
                    {
                        $php .= $this->generateComment( $method[ 'comment' ] );
                        $php .= PHP_EOL . $this->_inc;
                    }

                    // Write the accessor, if defined
                    if ( 'none' !== $accessor ) { $php .= "{$accessor} "; }

                    // Write 'final' or 'abstract' keywords if this is not a interface
                    if ( ! $this->isInterface )
                    {
                        if ( $method[ 'final' ] ) { $php .= 'final '; }
                        else if ( $method[ 'abstract' ] ) { $php .= 'abstract '; }
                    }

                    $php .= $mTypeStr . 'function ' . $method[ 'name' ] . '(';
                    if ( $this->prettyPrint && \count( $method[ 'params' ] ) > 0 )
                    {
                        $php .= ' ';
                    }

                    // Handle all defined parameters
                    $i = 0;
                    foreach ( $method[ 'params' ] as $param )
                    {

                        // Write parameter separators, beginning at 2nd parameter
                        if ( $i > 0 ) { $php .= ', '; }
                        else { $i++; }

                        // Write the parameter type, if defined
                        if ( null !== $param[ 'type' ] )
                        {
                            $php .= $param[ 'type' ] . ' ';
                        }

                        // Write the parameter variadic operator, if required
                        if ( $param[ 'variadic' ] )
                        {
                            $php .= '...';
                        }

                        // Write the "by reference" operator, if required
                        if ( $param[ 'byRef' ] )
                        {
                            $php .= '&';
                        }

                        // Write the parameter name including the leading dollar sign $
                        $php .= '$' . \ltrim( $param[ 'name' ], '$' );

                        // Write the default value, if defined
                        if ( null !== $param[ 'value' ] )
                        {

                            if ( $param[ 'isPhpValue' ] )
                            {
                                // Write PHP values as it
                                $php .= ( $this->prettyPrint ? ' = ' : '=' ) . $param[ 'value' ];
                            }
                            else
                            {
                                // Write regular values, converted to PHP code
                                $php .= ( $this->prettyPrint ? ' = ' : '=' )
                                        . $this->valueToPHPCode( $param[ 'value' ], $this->_inc );
                            }

                        }

                    }

                    // Close the method parameters by required round closing bracket ')'
                    $php .= ( $this->prettyPrint && \count( $method[ 'params' ] ) > 0 ? ' )' : ')' );

                    if ( null !== $method[ 'return' ] )
                    {
                        $php .= ' : ' . $method[ 'return' ];
                    }

                    if ( $method[ 'abstract' ] || $this->isInterface )
                    {
                        // End Method with semicolon if the method is abstract, or a part of a interface
                        $php .= ';';
                    }
                    else
                    {
                        // End the method with a empty method body
                        $php .= ' {}';
                    }

                }

                if ( $this->prettyPrint && 0 < \count( $methods ) ) { $php .= PHP_EOL . PHP_EOL . $this->_inc . '#endregion'; }

            }

        }

        return $php;

    }

    private function extractInterfaceData()
    {

        $interfaces = $this->_class->getInterfaceNames();

        if ( $this->isInterface )
        {
            // Interfaces do not implement other interfaces, they extend them
            $this->extends = $interfaces;
        }
        else
        {
            // Classes and traits implements the interfaces
            $this->implements = $interfaces;
        }

    }

    private function extractParentClassData()
    {

        if ( $parent = $this->_class->getParentClass() )
        {
            // There is a parent class
            if ( $parent->isInterface() )
            {
                // The parent is a interface :-(
                if ( $this->isInterface )
                {
                    $this->extends[] = $parent->getName();
                }
                else
                {
                    $this->implements[] = $parent->getName();
                }
            }
            else
            {
                // The parent is a class :-)
                $this->extends[] = $parent->getName();
            }
        }

    }

    private function extractConstantsData()
    {

        // Get information about all defined class constants
        $constants = $this->_class->getReflectionConstants();

        $this->constants = [];

        // Loop all constants
        foreach ( $constants as $const )
        {

            // Remember the class constant data
            $this->constants[] = $this->_extractConstPropData( $const, $const->getValue() );

        }

    }

    private function extractVarsData()
    {

        $this->vars       = [];
        $this->varsStatic = [];

        $defaultValues = $this->_class->getDefaultProperties();
        foreach ( $this->_class->getProperties() as $reflectionProperty )
        {
            if ( $reflectionProperty->isStatic() )
            {
                $this->varsStatic[] = $this->_extractConstPropData(
                    $reflectionProperty,
                    isset( $defaultValues[ $reflectionProperty->getName() ] )
                        ? $defaultValues[ $reflectionProperty->getName() ]
                        : null
                );
            }
            else
            {
                $this->vars[] = $this->_extractConstPropData(
                    $reflectionProperty,
                    isset( $defaultValues[ $reflectionProperty->getName() ] )
                        ? $defaultValues[ $reflectionProperty->getName() ]
                        : null
                );
            }
        }

    }

    private function extractMethodsData()
    {

        $this->methods       = [
            'instance' => [ 'public' => [], 'protected' => [], 'private' => [], 'none' => [] ],
            'static'   => [ 'public' => [], 'protected' => [], 'private' => [], 'none' => [] ]
        ];
        $this->methodCount = 0;

        foreach ( $this->_class->getMethods() as $meth )
        {

            $type      = $meth->isStatic() ? 'static' : 'instance';
            $accessor  = $meth->isPublic()
                ? 'public' : ( $meth->isPrivate() ? 'private' : ( $meth->isProtected() ? 'protected' : 'none' ) );

            $mData = [
                'name'     => $meth->getName(),
                'comment'  => $meth->getDocComment(),
                'final'    => $meth->isFinal(),
                'abstract' => $meth->isAbstract(),
                'return'   => null,
                'params'   => []
            ];

            if ( false === $mData[ 'comment' ] ) { $mData[ 'comment' ] = null; }

            $retType = $meth->getReturnType();

            if ( null !== $retType )
            {
                $mData[ 'return' ] = ( $retType->allowsNull() ? '?' : '' ) . \strval( $retType );
                if ( $mData[ 'return' ] === '?mixed' ||
                     $mData[ 'return' ] === '?null' ||
                     $mData[ 'return' ] === '?void' )
                {
                    $mData[ 'return' ] = \substr( $mData[ 'return' ], 1 );
                }
            }
            else if ( ! empty( $mData[ 'comment' ] ) )
            {
                if ( \preg_match( '~@return\\s+([^\\s\r\n\t]+)~', $mData[ 'comment' ], $matches ) )
                {
                    $mData[ 'return' ] = $this->_extractReturnTypeFromComment( $mData[ 'comment' ], $nullble );
                }
            }

            foreach ( $meth->getParameters() as $param )
            {

                $p = [
                    'name'       => $param->getName(),
                    'type'       => null,
                    'byRef'      => $param->isPassedByReference(),
                    'variadic'   => $param->isVariadic(), // ...$param (since PHP 5.6)
                    'value'      => null,
                    'isPhpValue' => false
                ];

                $nullable = $param->allowsNull();
                $pType = null;
                if ( $param->hasType() || $param->isArray() || $param->isCallable() )
                {
                    $pType = $param->getType();
                    if ( null === $pType )
                    {
                        $pType = $param->isArray() ? 'array' : ( $param->isCallable() ? 'callable' : null );
                    }
                }
                if ( null === $pType && ! empty( $mData[ 'comment' ] ) )
                {
                    $pType = $this->_extractParamTypeFromComment( $param, $mData[ 'comment' ], $nullable );
                }
                $p[ 'type' ] = $pType;

                if ( $param->isDefaultValueAvailable() )
                {
                    try
                    {
                        if ( $param->isDefaultValueConstant() )
                        {
                            $p[ 'value' ] = $param->getDefaultValueConstantName();
                            $p[ 'isPhpValue' ] = true;
                        }
                        else
                        {
                            $p[ 'value' ] = $param->getDefaultValue();
                        }
                    }
                    catch ( \Throwable $ex ) { $p[ 'value' ] = null; }
                }

                $mData[ 'params' ][] = $p;

            }

            $this->methods[ $type ][ $accessor ][] = $mData;

            $this->methodCount++;

        }

    }

    private function _extractConstPropData( $constOrProp, $value ) : array
    {

        // Gets constant modifier name, or NULL if no modifier is defined
        $mod = $constOrProp->isPublic()
            ? 'public'
            : ( $constOrProp->isPrivate()
                ? 'private'
                : ( $constOrProp->isProtected() ? 'protected' : null ) );

        // Remember the constant value
        $val  = static::PL === $value ? null : $value;

        // Get the type name, or NULL
        $type = \gettype( $val );
        switch ( $type )
        {
            case 'integer':
                $type = 'int';
                break;
            case 'boolean':
                $type = 'bool';
                break;
            case 'int':
            case 'bool':
            case 'float':
            case 'double':
            case 'string':
            case 'array':
                break;
            default:
                $type = null;
                break;
        }

        if ( null === $type )
        {
            $type = $this->_extractVarTypeFromComment( $constOrProp->getDocComment(), $nullable );
        }

        // Remember the class constant data
        return [
            'name'     => $constOrProp->getName(),
            'comment'  => $constOrProp->getDocComment(),
            'modifier' => $mod,
            'value'    => $val,
            'type'     => $type
        ];

    }

    private function _extractParamTypeFromComment( \ReflectionParameter $param, string $comment, bool &$nullable ) : ?string
    {

        if ( ! \preg_match( '~@param\\s+\\$' . \preg_quote( \ltrim( $param->getName(), '$' ), '~' ) .
                            '\\s+([a-zA-Z0-9_\\\\]+)~', $comment, $matches ) )
        {
            // The parameter is not commented
            return null;
        }

        return static::commentTypeStrToPhp( $matches[ 1 ], $nullable );

    }

    private function _extractReturnTypeFromComment( string $comment, bool &$nullable ) : ?string
    {

        if ( ! \preg_match( '~@return\\s+([a-zA-Z0-9_\\\\]+)~', $comment, $matches ) )
        {
            // The parameter is not commented
            return null;
        }

        return static::commentTypeStrToPhp( $matches[ 1 ], $nullable );

    }

    private function _extractVarTypeFromComment( string $comment, bool &$nullable ) : ?string
    {

        if ( ! \preg_match( '~@var\\s+([a-zA-Z0-9_\\\\]+)~', $comment, $matches ) )
        {
            // The parameter is not commented
            return null;
        }

        return static::commentTypeStrToPhp( $matches[ 1 ], $nullable );

    }

    private function _generateVars( array $vars, bool $isStatic ) : string
    {

        // No Vars => no output
        if ( 1 > \count( $vars ) )
        {
            return '';
        }

        // Leading empty lines before the signature
        $php = PHP_EOL . ( $this->prettyPrint ? PHP_EOL : '' );

        // Init the array for separating vars with 4 different modifiers
        $signs = [ 'public' => [], 'protected' => [], 'private' => [], 'none' => [] ];

        // Loop as defined vars of the class
        foreach ( $vars as $varData )
        {

            // Ensure the modifier has one of the required 4 values
            if ( empty( $varData[ 'modifier' ] ) ) { $varData[ 'modifier' ] = 'none'; }

            // Get the index of the next added (this) var signature
            $i = \count( $signs[ $varData[ 'modifier' ] ] );

            // Generate the comment PHP
            $commentCode = $this->generateComment( $varData[ 'comment' ] );

            // Add comment and linebreak+increment before and after
            $signs[ $varData[ 'modifier' ] ][] = PHP_EOL . $this->_inc;
            if ( '' !== $commentCode )
            {
                $signs[ $varData[ 'modifier' ] ][ $i ] .= $this->generateComment( $varData[ 'comment' ] )
                                                          . PHP_EOL . $this->_inc;
            }

            // Write the modifier, if defined
            if ( 'none' !== $varData[ 'modifier' ] )
            {
                $signs[ $varData[ 'modifier' ] ][ $i ] .= \strtolower( $varData[ 'modifier' ] ) . ' ';
            }

            // Add the 'static' modifier if required
            if ( $isStatic )
            {
                $signs[ $varData[ 'modifier' ] ][ $i ] .= 'static ';
            }

            // Write the variable name
            $signs[ $varData[ 'modifier' ] ][ $i ] .= '$' . \ltrim( $varData[ 'name' ], '$' );

            // Write the associated value, if defined
            if ( null !== $varData[ 'value' ] )
            {
                $signs[ $varData[ 'modifier' ] ][ $i ] .= ' = '
                                                          . $this->valueToPHPCode( $varData[ 'value' ], $this->_inc );
            }
            // Write the final end semicolon
            $signs[ $varData[ 'modifier' ] ][ $i ] .= ';';

        }

        // Now the class var signatures are ordered by modifier and can be printed
        $php .= $this->__generateVars( $signs[ 'public' ], 'P U B L I C   ' . ( $isStatic ? 'S T A T I C   ' : '' ) );
        $php .= $this->__generateVars( $signs[ 'protected' ], 'P R O T E C T E D   ' . ( $isStatic ? 'S T A T I C   ' : '' ) );
        $php .= $this->__generateVars( $signs[ 'private' ], 'P R I V A T E   ' . ( $isStatic ? 'S T A T I C   ' : '' ) );
        $php .= $this->__generateVars( $signs[ 'none' ], $isStatic ? 'S T A T I C   ' : '' );

        $php .= PHP_EOL;

        return $php;

    }

    private function __generateVars( $vars, string $regionText ) : string
    {

        $php = '';

        if ( 1 > \count( $vars ) )
        {
            return $php;
        }

        // Use regions if pretty print is enabled
        if ( $this->prettyPrint )
        {
            $php .= PHP_EOL . PHP_EOL . $this->_inc . '#region // ' . $regionText . 'C L A S S   F I E L D S' . PHP_EOL;
        }

        $php .= \implode( $this->prettyPrint ? PHP_EOL : '', $vars );

        if ( $this->prettyPrint ) { $php .= PHP_EOL . PHP_EOL . $this->_inc . '#endregion' . PHP_EOL; }

        return $php;

    }

    /**
     * @param mixed  $value
     * @param string $increment
     *
     * @return string
     * @noinspection RegExpSingleCharAlternation
     */
    private function valueToPHPCode( $value, string $increment ) : string
    {

        if ( null === $value ) { return 'NULL'; }

        if ( \is_int( $value ) || \is_float( $value ) || \is_double( $value ) ) { return \strval( $value ); }

        if ( \is_bool( $value ) ) { return $value ? 'true' : 'false'; }

        if ( \is_string( $value ) )
        {
            $sQuote = false !== \strpos( $value, "'" );
            $dQuote = false !== \strpos( $value, '"' );
            $hasESq = (bool) \preg_match( '~(\\r|\\n|\\t|\\\\)~', $value );
            $value = \str_replace(
                [ "\r", "\n", "\t", "\0", "\\" ],
                [ '\r', '\n', '\t', '\0', "\\\\" ],
                $value
            );
            if ( ! $sQuote && ! $dQuote )
            {
                return $hasESq ? ( '"' . $value . '"' ) : "'{$value}'";
            }
            if ( $sQuote )
            {
                if ( $dQuote )
                {
                    return '"' . \str_replace( '"', '\\"', $value ) . '"';
                }
                else
                {
                    return '"' . $value . '"';
                }
            }
            return $hasESq ? ( '"' . \str_replace( '"', '\\"', $value ) . '"' ) : "'{$value}'";
        }

        if ( \is_array( $value ) )
        {
            $amount = \count( $value );
            if ( 1 > $amount )
            {
                return '[]';
            }
            $str   = '[';
            $isNum = static::IsNumericIndicated( $value );
            $idx   = 0;
            $lbr   = $this->prettyPrint ? [ "\r\n", ",\r\n" ] : [ " ", ", " ];
            $inc   = $this->prettyPrint ? $increment . '    ' : '';
            foreach ( $value as $k => $v )
            {
                $str .= $lbr[ ( $idx < 1 ) ? 0 : 1 ];
                $idx++;
                if ( $isNum )
                {
                    $str .= $inc . static::valueToPHPCode( $v, $increment . '    ' );
                    continue;
                }
                if ( \is_int( $k ) )
                {
                    $str .= $inc . '    ' . $k . ' => ' . static::valueToPHPCode( $v, $increment . '    ' );
                    continue;
                }
                $str .= $inc . '    ' . static::valueToPHPCode( $k, $increment . '    ' )
                        . ' => ' . static::valueToPHPCode( $v, $increment . '    ' );
            }
            $str .= $lbr[ 0 ] . ( $this->prettyPrint ? $increment : '' ) . ']';
            return $str;
        }

        return '"ERROR! --- UNKNOWN VALUE TYPE ---"';

    }

    #endregion


    #region // P U B L I C   S T A T I C   M E T H O D S

    /**
     * Returns if the defined array is numerically indicated. (0-n)
     *
     * @param  array $array The array to check
     * @return boolean
     */
    public static function IsNumericIndicated( array $array ) : bool
    {

        $itemCount = \count( $array );

        if ( $itemCount < 1 ) { return true; }

        // Create the representative value (the joined array keys must be equal to it)
        $nums = \implode( '', \range( 0, $itemCount - 1 ) );

        // check the required array keys with the given.
        return ( $nums === \implode( '', \array_keys( $array ) ) );

    }

    /**
     * @param string $arrayValue
     *
     * @return string
     */
    public static function NormalizeTypesArrayValue( string $arrayValue ) : string
    {
        $trimmed = \trim( $arrayValue );
        if ( 'null' === \strtolower( $trimmed ) ) { return 'null'; }
        return $trimmed;
    }

    #endregion


    private static function commentTypeStrToPhp( string $typeStr, bool &$refNullable )
    {

        // split in single types if separated by a pipe | and trim the values and normalize null to lower case
        $tmp = \array_map( 'ClassReflector::NormalizeTypesArrayValue', \explode( '|', $typeStr ) );

        // if $tmp contains only a single type
        if ( \count( $tmp ) < 2 )
        {
            $pType = \trim( $typeStr );
            $refNullable = false;
        }

        // if $tmp contains 2 types
        else if ( \count( $tmp ) < 3 )
        {
            $refNullable = \in_array( 'null', $tmp );
            if ( $refNullable && false !== ( $pos = \array_search( 'null', $tmp ) ) )
            {
                unset( $tmp[ $pos ] );
                $tmp = \array_unique( $tmp );
                $pType = $tmp[ 0 ];
            }
            else
            {
                $pType = null;
                $refNullable = false;
            }
        }

        // if $tmp contains more than 2 types
        else
        {
            $pType = null;
            $refNullable = false;
        }

        if ( null === $pType || 'void' === $pType ) { return null; }

        return ( $refNullable ? '?' : '' ) . $pType;

    }


}

