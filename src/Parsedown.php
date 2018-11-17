<?php declare(strict_types=1);

namespace Nessworthy\ParsedownExtensionManager;

use Nessworthy\ParsedownExtension\ParsedownBlockExtension;
use Nessworthy\ParsedownExtension\ParsedownExtension;
use Nessworthy\ParsedownExtension\ParsedownInlineExtension;
use Parsedown as ParsedownBase;

/**
 * @package Nessworthy\ParsedownExtensionManager
 */
class Parsedown extends ParsedownBase implements ParsedownExtensionManager
{
    private const EXTENSION_TYPE_BLOCK = 'block';
    private const EXTENSION_TYPE_INLINE = 'inline';

    private $registeredExtensions = [
        self::EXTENSION_TYPE_BLOCK => [],
        self::EXTENSION_TYPE_INLINE => [],
    ];

    /**
     * Register a new block extension with Parsedown.
     * @param ParsedownBlockExtension $extension
     */
    public function registerBlockExtension(ParsedownBlockExtension $extension): void
    {
        $extensionIdentifier = $this->generateExtensionIdentity($extension);
        $extensionCharacter = $this->getExtensionCharacter($extension);

        $this->registeredExtensions[self::EXTENSION_TYPE_BLOCK][$extensionIdentifier] = $extension;

        if (!isset($this->BlockTypes[$extensionCharacter])) {
            $this->BlockTypes[$extensionCharacter] = [];
        }

        $this->BlockTypes[$extensionCharacter][] = $extensionIdentifier;
    }

    /**
     * Register a new inline extension with Parsedown.
     * @param ParsedownInlineExtension $extension
     */
    public function registerInlineExtension(ParsedownInlineExtension $extension): void
    {
        $extensionIdentifier = $this->generateExtensionIdentity($extension);
        $extensionCharacter = $this->getExtensionCharacter($extension);

        $this->registeredExtensions[self::EXTENSION_TYPE_INLINE][$extensionIdentifier] = $extension;

        if (!isset($this->InlineTypes[$extensionCharacter])) {
            $this->InlineTypes[$extensionCharacter] = [];
        }

        $this->InlineTypes[$extensionCharacter][] = $extensionIdentifier;
        $this->addToMarkerList($extensionCharacter);
    }

    /**
     * Override Parsedown's native continue block detection.
     * Checks matching registered extensions.
     * @param $type
     * @return bool
     */
    protected function isBlockContinuable($type): bool
    {
        return parent::isBlockContinuable($type)
            || isset($this->registeredExtensions[self::EXTENSION_TYPE_BLOCK][$type]);
    }

    /**
     * Override Parsedown's native continue complete detection.
     * Checks matching registered extensions.
     * @param $type
     * @return bool
     */
    protected function isBlockCompletable($type): bool
    {
        return parent::isBlockCompletable($type)
            || isset($this->registeredExtensions[self::EXTENSION_TYPE_BLOCK][$type]);
    }

    /**
     * Locate a block extension by identifier.
     * @param string $extensionName
     * @return ParsedownBlockExtension
     */
    private function findBlockExtension(string $extensionName): ParsedownBlockExtension
    {
        if (!isset($this->registeredExtensions[self::EXTENSION_TYPE_BLOCK][$extensionName])) {
            throw new ExtensionNotFoundException('Block extension ' . $extensionName . ' not found!');
        }
        return $this->registeredExtensions[self::EXTENSION_TYPE_BLOCK][$extensionName];
    }

    /**
     * Locate an inline extension by identifier.
     * @param string $extensionName
     * @return ParsedownInlineExtension
     */
    private function findInlineExtension(string $extensionName): ParsedownInlineExtension
    {
        if (!isset($this->registeredExtensions[self::EXTENSION_TYPE_INLINE][$extensionName])) {
            throw new ExtensionNotFoundException('Inline extension ' . $extensionName . ' not found!');
        }
        return $this->registeredExtensions[self::EXTENSION_TYPE_INLINE][$extensionName];
    }

    /**
     * The magic which allows us to override Markdown.
     * We listen for the internal calls Parsedown attempts and reroute them to
     * each relevant extension.
     * @param $name
     * @param $arguments
     * @return array|null
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'block') === 0) {
            if (strpos($name, 'Continue') === \strlen($name) - 8) {
                $extensionName = substr($name, 5, -8);
                $extension = $this->findBlockExtension($extensionName);
                return $extension->continue($arguments[0], $arguments[1]);
            }

            if (strpos($name, 'Complete') === \strlen($name) - 8) {
                $extensionName = substr($name, 5, -8);
                $extension = $this->findBlockExtension($extensionName);
                return $extension->complete($arguments[0]);
            }

            $extensionName = substr($name, 5);
            $extension = $this->findBlockExtension($extensionName);
            return $extension->start($arguments[0], $arguments[1]);
        }

        if (strpos($name, 'inline') === 0) {
            $extensionName = substr($name, 6);
            $extension = $this->findInlineExtension($extensionName);
            return $extension->run($arguments[0]);
        }

        throw new \Error('Call to undefined method ' . __CLASS__ . '::' . $name);
    }

    /**
     * Add the extension's starting character to the inline marker list.
     * Checks for duplicates.
     * @param string $extensionCharacter
     */
    private function addToMarkerList(string $extensionCharacter): void
    {
        if (strpos($this->inlineMarkerList, $extensionCharacter) === false) {
            $this->inlineMarkerList .= $extensionCharacter;
        }
    }

    /**
     * Generate a unique identifier for this instance of a Parsedown extension.
     * @param ParsedownExtension $extension
     * @return string
     */
    private function generateExtensionIdentity(ParsedownExtension $extension): string
    {
        return spl_object_hash($extension);
    }

    /**
     * Fetch the starting character for an extension.
     * @param ParsedownExtension $extension
     * @throws \InvalidArgumentException If the extension's starting character is empty.
     * @return string
     */
    private function getExtensionCharacter(ParsedownExtension $extension): string
    {
        $extensionIdentifier = $extension->getStartingCharacter();
        if (empty($extensionIdentifier)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Extension %s is missing a starting character!',
                    \get_class($extension)
                )
            );
        }

        return $extension->getStartingCharacter()[0];
    }
}
