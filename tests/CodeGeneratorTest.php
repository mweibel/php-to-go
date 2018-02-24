<?php

namespace PHPToGo\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPToGo\CodeGenerator;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

AnnotationRegistry::registerAutoloadNamespace('JMS\Serializer', 'vendor/jms/serializer/src');

class CodeGeneratorTest extends TestCase
{
    /**
     * @var string
     */
    private $goPath;
    /**
     * @var string
     */
    private $targetDirectory;
    /**
     * @var TemporaryDirectory
     */
    private $tempDirectory;

    protected function setUp()
    {
        parent::setUp();

        $this->tempDirectory = (new TemporaryDirectory())->create();

        $this->goPath = $this->tempDirectory->path('gopath');
        $this->targetDirectory = $this->goPath.'/src/github.com/mweibel/php-to-go-tests';

        @mkdir($this->targetDirectory, 0777, true);

        putenv('GOPATH='.realpath($this->goPath));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->tempDirectory->delete();
    }

    public function testCodeGenerator()
    {
        $fixturePath = dirname(__FILE__).'/fixtures';
        $generator = new CodeGenerator($fixturePath.'/*.php', $this->targetDirectory, 'models', ['IgnoredClass.php'], ['ignoredPropertyName'], false);
        $generator->generate();

        $expectedDir = dirname(__FILE__).'/output';
        $files = [];
        foreach (glob($expectedDir.'/*.go') as $expectedFile) {
            $name = basename($expectedFile);

            $files[$name] = true;

            $this->assertFileEquals($expectedFile, $this->targetDirectory.'/'.$name);
        }
        foreach (glob($this->targetDirectory.'/*.go') as $actualFile) {
            $name = basename($actualFile);
            if (isset($files[$name])) {
                continue;
            }

            $files[$name] = true;

            $this->assertFileEquals($expectedDir.'/'.$name, $actualFile);
        }
    }
}
