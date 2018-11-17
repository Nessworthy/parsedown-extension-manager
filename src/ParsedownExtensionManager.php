<?php declare(strict_types=1);

namespace Nessworthy\ParsedownExtensionManager;

use Nessworthy\ParsedownExtension\ParsedownBlockExtension;
use Nessworthy\ParsedownExtension\ParsedownInlineExtension;

interface ParsedownExtensionManager
{
    /**
     * Register a new block extension with Parsedown.
     * @param ParsedownBlockExtension $extension
     */
    public function registerBlockExtension(ParsedownBlockExtension $extension): void;

    /**
     * Register a new inline extension with Parsedown.
     * @param ParsedownInlineExtension $extension
     */
    public function registerInlineExtension(ParsedownInlineExtension $extension): void;
}