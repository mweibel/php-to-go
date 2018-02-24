<?php

namespace PHPToGo;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\IndexedReader;
use JMS\Serializer\TypeParser;

class CodeGenerator
{
    private const PHP_TO_GO_TYPES = [
        'string'                => 'string',
        'integer'               => 'int',
        'int'                   => 'int',
        'boolean'               => 'bool',
        'float'                 => 'float64',
        'DateTime'              => 'datatypes.DateTime',
        'Date'                  => 'datatypes.Date',
        'DateTimeImmutable'     => 'datatypes.Date',
        'IntlDate'              => 'datatypes.IntlDate',
    ];

    /**
     * @var string
     */
    private $srcGlob;
    /**
     * @var string
     */
    private $targetDirectory;
    /**
     * @var string Ignored files from input dir
     */
    private $ignoredFiles;
    /**
     * @var string[] a map of property names to ignore
     */
    private $ignoredPropertyNames;
    /**
     * @var bool
     */
    private $verbose;
    /**
     * @var string
     */
    private $packagePath;
    /**
     * @var IndexedReader
     */
    private $reader;
    /**
     * @var array
     */
    private $typeMap;
    /**
     * @var TypeParser
     */
    private $typeParser;
    /**
     * @var string[] List of known namespaces which contain types we parse.
     */
    private $knownNamespaces = [];

    /**
     * @param string $srcGlob              Glob to find all source PHP files
     * @param string $targetDirectory      Target directory within GOPATH
     * @param string $packageName          Go package name of the generated files
     * @param array  [$ignoredFiles]       List of files to ignore within the target directory
     * @param array  $ignoredPropertyNames List of property names of mdoels to ignore
     * @param bool   $verbose              Whether to echo some status during the generation
     */
    public function __construct(string $srcGlob, string $targetDirectory, string $packageName, array $ignoredFiles = [], array $ignoredPropertyNames = [], bool $verbose = true)
    {
        if (!is_dir($targetDirectory)) {
            throw new \InvalidArgumentException('targetDirectory needs to be a valid directory');
        }
        $this->srcGlob = $srcGlob;
        $this->targetDirectory = $targetDirectory;
        $this->packageName = $packageName;
        $this->ignoredFiles = $ignoredFiles;

        // convert simple array to map for easier lookup
        foreach ($ignoredPropertyNames as $name) {
            $this->ignoredPropertyNames[$name] = true;
        }
        $this->verbose = $verbose;

        $this->packagePath = $this->guessPackagePath($targetDirectory);


        $this->reader = new IndexedReader(new AnnotationReader());
        $this->typeMap = [];
        $this->typeParser = new TypeParser();
    }

    public function generate()
    {
        $this->copyDataTypes();

        foreach (glob($this->srcGlob) as $file) {
            $ignore = false;
            foreach ($this->ignoredFiles as $ignoredFile) {
                if (false !== strpos($file, $ignoredFile)) {
                    $ignore = true;
                    break;
                }
            }
            if (!$ignore) {
                $this->generateFile($file);
            }
        }

        $exec = 'gofmt -w '.$this->targetDirectory;
        $this->log('Successfully wrote all models. Executing '.$exec);
        $retVal = shell_exec($exec);
        if (null !== $retVal) {
            $this->log($retVal);
        }
    }

    private function generateFile(string $fileName)
    {
        $this->log($fileName);

        try {
            $type = new Type($fileName);
        } catch (IgnoreFileException $e) {
            $this->log($e->getMessage());
            return;
        }
        // if a type has already been processed, ignore.
        if (isset($this->typeMap[$type->getFullClassName()])) {
            return;
        }
        $this->typeMap[$type->getFullClassName()] = $type;
        $this->knownNamespaces[$type->getNamespace()] = true;
        $this->generateModel($type);
    }

    /**
     * Guess Go package path based on target directory (i.e. minus $GOPATH should be the dir)
     *
     * @param string $dir
     * @return string
     */
    private function guessPackagePath(string $dir): string
    {
        $absolute = realpath($dir);
        $goPath = realpath(implode(DIRECTORY_SEPARATOR, [getenv('GOPATH'), 'src']));
        return str_replace($goPath.'/', '', $absolute);
    }

    private function generateModel(Type $type)
    {
        $reflClass = new \ReflectionClass($type->getFullClassName());
        $attrs = [];
        $needsAfterMarshal = false;

        // needs after marshal is determined by having methods with a JMS\Serializer annotation in the model
        // These most likely have specific PHP code on how to serialize certain properties -> can't be auto translated at the moment.
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
                $annotationClass = get_class($annotation);
                if (0 === strpos($annotationClass, 'JMS\Serializer')) {
                    $needsAfterMarshal = true;
                    break;
                }
            }
        }

        foreach ($reflClass->getProperties() as $property) {
            if (isset($this->ignoredPropertyNames[$property->getName()])) {
                continue;
            }

            $propertyAnnotations = [];

            foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                $propertyAnnotations = array_merge($propertyAnnotations, $this->parsePropertyAnnotation($annotation));
            }

            if (count($propertyAnnotations)) {
                $attrs[$property->getName()] = $propertyAnnotations;
            }
        }
        $type->update($attrs, $needsAfterMarshal);
        $type->write($this->targetDirectory, $this->packageName, $this->packagePath);
    }

    /**
     * @param mixed $annotation
     * @return array
     */
    private function parsePropertyAnnotation($annotation): array
    {
        $propertyAnnotations = [];
        $annotationClass = get_class($annotation);

        if (0 === strpos($annotationClass, 'JMS\Serializer')) {
            $annotationType = strtolower(substr($annotationClass, strlen('JMS\Serializer\Annotation\\')));
            if ($annotationType === 'exclude') {
                return $propertyAnnotations;
            }

            $propertyAnnotations[$annotationType] = [];

            $annotationReflClass = new \ReflectionClass($annotationClass);
            $props = $annotationReflClass->getProperties();
            $propsCount = count($props);

            foreach ($annotationReflClass->getProperties() as $attrProperty) {
                $name = $attrProperty->getName();
                $value = $annotation->$name;

                if (!$value) {
                    continue;
                }
                if (1 === $propsCount) {
                    $propertyAnnotations[$annotationType] = $value;
                    break;
                }
                $propertyAnnotations[$annotationType][$name] = $value;
            }

            if ('type' === $annotationType) {
                $originalType = $propertyAnnotations['type'];
                if ('array' === $originalType) {
                    return [];
                }

                $newType = $this->parseType($this->typeParser->parse($originalType));
                $propertyAnnotations['type'] = $newType;

                return $propertyAnnotations;
            }
        }

        return $propertyAnnotations;
    }

    private function parseType(array $type): string
    {
        switch (count($type['params'])) {
            case 0:
                $typ = $this->convertPHPToGoType($type['name']);
                if (null !== $typ) {
                    return $typ;
                }
                $this->log(print_r($typ, true));
                throw new \RuntimeException("Unknown type '${type['name']}'");
            case 1:
                $param = $type['params'][0];
                if ($type['name'] === 'DateTime') {
                    switch ($param) {
                        case 'd.m.Y':
                            return $this->convertPHPToGoType('Date');
                        case 'Y-m-d':
                            return $this->convertPHPToGoType('IntlDate');
                        default:
                            throw new \RuntimeException("Param type DateTime<".$param."> not implemented.");
                    }
                }
                return "[]" . $this->parseType($param);
            case 2:
                return "map[" . $this->parseType($type['params'][0]) . "]" . $this->parseType($type['params'][1]);
                break;
            default:
                throw new \RuntimeException('More than 2 params for a type not implemented');
        }
    }

    private function convertPHPToGoType(string $type): ?string
    {
        if (isset(self::PHP_TO_GO_TYPES[$type])) {
            return self::PHP_TO_GO_TYPES[$type];
        }
        foreach ($this->knownNamespaces as $ns => $ignore) {
            if (0 === strpos($type, $ns)) {
                // get classname without namespace
                return substr($type, strrpos($type, '\\')+1);
            }
        }
        return null;
    }

    /**
     * @param $str
     */
    private function log(string $str)
    {
        if ($this->verbose) {
            echo $str."\n";
        }
    }

    private function copyDataTypes()
    {
        $dest = implode(DIRECTORY_SEPARATOR, [$this->targetDirectory, 'datatypes']);
        @mkdir($dest);
        $fileGlob = implode(DIRECTORY_SEPARATOR, ['.', 'datatypes', '*.go']);
        foreach (glob($fileGlob) as $file) {
            @copy($file, $dest.DIRECTORY_SEPARATOR.basename($file));
        }
    }
}
