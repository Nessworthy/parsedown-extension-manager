<?php declare(strict_types=1);

namespace Nessworthy\ParsedownExtensionManager\Tests;

use Nessworthy\ParsedownExtension\ParsedownBlockExtension;
use Nessworthy\ParsedownExtension\ParsedownInlineExtension;
use Nessworthy\ParsedownExtensionManager\ExtensionNotFoundException;
use Nessworthy\ParsedownExtensionManager\Parsedown;

class ParsedownTest extends \PHPUnit\Framework\TestCase
{
    private function setupFakeBlockExtension(): ParsedownBlockExtension
    {
        $mockBuilder = $this->getMockBuilder(ParsedownBlockExtension::class);

        $mock = $mockBuilder->getMock();

        $mock
            ->method('getStartingCharacter')
            ->willReturn(':');

        $mock
            ->method('start')
            ->willReturn(['char' => ':', 'element' => ['name' => 'example', 'text' => 'test']]);

        $mock
            ->method('continue')
            ->willReturnCallback(function($instance, $line, array $block) {
                $block['complete'] = true;
                return $block;
            });

        $mock
            ->method('complete')
            ->willReturnArgument(1);

        return $mock;
    }

    private function setupFakeInlineExtension(): ParsedownInlineExtension
    {
        $mockBuilder = $this->getMockBuilder(ParsedownInlineExtension::class);

        $mock = $mockBuilder->getMock();

        $mock
            ->method('getStartingCharacter')
            ->willReturn(':');

        $mock
            ->method('run')
            ->willReturn(['extent' => 1, 'element' => ['name' => 'example', 'text' => 'test']]);

        return $mock;
    }

    public function testBlockExtension()
    {
        $parsedown = new Parsedown();
        $extension = $this->setupFakeBlockExtension();

        $parsedown->registerBlockExtension($extension);
        $result = $parsedown->parse(':');

        $this->assertEquals('<example>test</example>', $result);
    }

    public function testInlineExtension()
    {
        $parsedown = new Parsedown();
        $extension = $this->setupFakeInlineExtension();

        $parsedown->registerInlineExtension($extension);
        $result = $parsedown->parse(':');

        $this->assertEquals('<p><example>test</example></p>', $result);
    }

    public function testInvalidMethodCallThrowsBadMethodCallException()
    {
        $parsedown = new Parsedown();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method Nessworthy\ParsedownExtensionManager\Parsedown::methodThatDoesntExist');

        $parsedown->methodThatDoesntExist();
    }

    public function testUnregisteredBlockExtensionCallThrowsRuntimeException()
    {
        $parsedown = new Parsedown();

        $this->expectException(ExtensionNotFoundException::class);
        $this->expectExceptionMessage('Block extension FakeExtension not found!');

        $parsedown->blockFakeExtensionContinue();
    }

    public function testUnregisteredInlineExtensionCallThrowsRuntimeException()
    {
        $parsedown = new Parsedown();

        $this->expectException(ExtensionNotFoundException::class);
        $this->expectExceptionMessage('Inline extension FakeExtension not found!');

        $parsedown->inlineFakeExtension();
    }
}