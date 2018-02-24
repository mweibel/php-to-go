<?php

namespace PHPToGo;

class Type
{
    /**
     * Reserved words in Go.
     */
    private const RESERVED_WORDS = ['package', 'type'];

    /**
     * @var string
     */
    private $fileName;
    /**
     * @var string
     */
    private $content;
    /**
     * @var string
     */
    private $fullClassName;
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var string
     */
    private $className;
    /**
     * @var array
     */
    private $attrs;
    /**
     * @var bool
     */
    private $needsAfterMarshal;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $content = file_get_contents($fileName);
        if (false === $content) {
            throw new \RuntimeException('Unable to read file '.$fileName);
        }
        $this->content = $content;
        $this->fullClassName = $this->extractFullQualifiedClassName();
        $last = strrpos($this->fullClassName, '\\');
        $this->className = substr($this->fullClassName, $last+1);
        $this->namespace = substr($this->fullClassName, 0, $last);
    }

    /**
     * @param array $attrs
     * @param bool $needsAfterMarshal
     */
    public function update(array $attrs, bool $needsAfterMarshal)
    {
        $this->attrs = $attrs;
        $this->needsAfterMarshal = $needsAfterMarshal;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getFullClassName(): string
    {
        return $this->fullClassName;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function write(string $targetDirectory, string $packageName, string $packagePath)
    {
        $path = implode(DIRECTORY_SEPARATOR, [$targetDirectory, strtolower($this->className) . '_generated.go']);
        $file = fopen($path, 'w');

        $this->writeHeader($file, $packageName, $packagePath);
        $this->writeStruct($file);

        fclose($file);
    }

    private function writeHeader($file, string $packageName, string $packagePath)
    {
        fwrite($file, sprintf("package %s\nimport (\"%s/datatypes\"\n", $packageName, $packagePath));
        fwrite($file, "\"github.com/liip/sheriff\")\n");
    }

    private function writeStruct($file)
    {
        fwrite($file, sprintf("type %s struct {\n", $this->className));

        foreach ($this->attrs as $field => $value) {
            $this->writeAttr($file, $field, $value);
        }

        fwrite($file, "\n}\n");
        fwrite($file, sprintf("func (data %s) Marshal(options *sheriff.Options) (interface{}, error) {\n", $this->className));
        fwrite($file, "dest, err := sheriff.Marshal(options, data)\n");
        fwrite($file, "if err != nil {\n");
        fwrite($file, "return nil, err\n");
        fwrite($file, "}\n"); // if err != nil

        if ($this->needsAfterMarshal) {
            fwrite($file, "return data.AfterMarshal(options, dest)\n");
        } else {
            fwrite($file, "return dest, nil\n");
        }
        fwrite($file, "}\n"); // func
    }

    private function writeAttr($file, string $field, array $value)
    {
        $fieldName = $value['serializedname'] ?? $this->camelToSnake($field);
        $tag = sprintf('json:"%s,omitempty" ', $fieldName);
        foreach ($value as $key => $valueValue) {
            if ($key !== 'type' && $key !== 'serializedname' && null !== $valueValue) {
                if (is_array($valueValue)) {
                    $valueValue = implode(',', $valueValue);
                }
                $tag .= sprintf('%s:"%s" ', $key, $valueValue);
            }
        }

        if (false !== array_search($field, self::RESERVED_WORDS)) {
            $field .= 'Field';
        }

        if (!array_key_exists('type', $value)) {
            fwrite($file, "// Warning: The following property has no 'TYPE' annotation!! Check the model\n//");
            $value['type'] = 'UNKNOWN';
        }

        fwrite($file, sprintf("\t%s *%s `%s`\n", ucfirst($field), $value['type'], trim($tag)));
    }

    private function camelToSnake(string $str): string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $str)), '_');
    }

    /**
     * From: http://stackoverflow.com/questions/7153000/get-class-name-from-file
     * @return string
     */
    private function extractFullQualifiedClassName(): string
    {
        $tokens = token_get_all($this->content);
        $class = $namespace = '';
        $namespaceStarted = false;
        $classStarted = false;

        for ($i = 0, $l = count($tokens); $i < $l; $i++) {
            if ($tokens[$i] === ';') {
                $namespaceStarted = false;
                continue;
            }
            if ($tokens[$i] === '{') {
                $classStarted = false;
                return $namespace.$class;
            }
            switch ($tokens[$i][0]) {
                case T_NAMESPACE:
                    $namespaceStarted = true;
                    break;
                case T_CLASS:
                    $classStarted = true;
                    break;
                case T_EXTENDS:
                    // fallthrough
                case T_IMPLEMENTS:
                    $classStarted = false;
                    return $namespace.$class;
                case T_STRING:
                    if ($namespaceStarted) {
                        $namespace .= $tokens[$i][1] . '\\';
                        break;
                    }
                    if ($classStarted) {
                        $class .= $tokens[$i][1];
                        break;
                    }
                    break;
                case T_INTERFACE:
                    throw new IgnoreFileException('Interfaces are ignored');
            }
        }
        throw new \RuntimeException('Should never reach that point.');
    }
}
