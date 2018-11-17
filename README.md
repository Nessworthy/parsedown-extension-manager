# Parsedown Extensions

A tiny project which introduces a new way to create and use markdown extensions with [erusev/parsedown][1]!

## Requirements

* PHP 7.1

## Installation

It's a composer installation away!

```
composer require nessworthy/parsedown-extension-manager
```

## Why?

Each extension added to Parsedown must be done by extending it, registering the extension in a few places, and adding
1-3 new methods in the extended class.

After adding in a few extensions the original way, I grew a little frustrated at how "vertical" the markdown
class was becoming. 

So, I decided to change the way extensions could be registered.

## What's new?

Extensions can be represented as concrete classes of one of two interfaces: `ParsedownBlockExtension`, or `ParsedownInlineExtension`.

Each extension is then separately instantiated and registered to your `Nessworthy\ParsedownExtensionManager\Parsedown` instance by using the added
`registerBlockExtension` or `registerInlineExtension` and passing your extension through.

Parsedown will use your extensions in the same way as it normally would with the benefit of each extension being isolated
and separately extendable!

## Usage Example

### Step 1: Create your Extension!

Extensions can either implement [`\Nessworthy\ParsedownExtensionManager\ParsedownInlineExtension`][2] or
[`\Nessworthy\ParsedownExtensionManager\ParsedownBlockExtension`][3]. Both expect methods which mirror closely to how you would
add extensions normally.

```php
<?php

use \Nessworthy\ParsedownExtensionManager\Parsedown;

/**
 * This is an implementation of the "Add Inline Element"  example in the parsedown docs.
 * @see https://github.com/erusev/parsedown/wiki/Tutorial:-Create-Extensions#add-inline-element
 */
class ExampleInlineExtension implements \Nessworthy\ParsedownExtensionManager\ParsedownInlineExtension
{
    public function getStartingCharacter(): string
    {
        return '{';
    }
    
    public function run(array $excerpt): ?array
    {
        if (preg_match('/^{c:([#\w]\w+)}(.*?){\/c}/', $excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]), 
                'element' => [
                    'name' => 'span',
                    'text' => $matches[2],
                    'attributes' => [
                        'style' => 'color: ' . $matches[1],
                    ],
                ],

            ];
        }
        
        return null;
    }
}

```

### Step 2: Instantiate & Register your Extension!

```php
<?php

// Create your Parsedown instance.
$parsedown = new \Nessworthy\ParsedownExtensionManager\Parsedown();

// Register your Parsedown extensions.
$parsedown->registerInlineExtension(new ExampleInlineExtension());

// Use Parsedown as you normally would!
$parsedown->parse('Hello {c:#FF00000}world{/c}!');
// "<p>Hello <span style="color: #FF0000">world!</span></p>
``` 

## What's the catch?

Mm, good question. Let me know and I'll put it here!

Parsedown is still fundamentally the same, with the added functionality of seperate extension registration.
You can still extend this class and add parsedown extensions the original way!

Ignorance aside, this library does leverage `__call` and tries to do so
as sanely as possible.

In addition, because this is _yet another_ extension of Parsedown, it won't work out of the box with any of the other
Parsedown extensions out there. However, it's possible to simply convert other Parsedown extensions to work with
this library instead!

## Distributing Extensions

If you fancy creating and sharing extensions of your own, feel free to use the `nessworthy\parsedown-extension` 
metapackage instead which only contains the interfaces you need to implement.

## What's next?

* More accurate written tests! (e.g. to account for pre-registered inline special characters and not yet registered ones)

[1]:https://github.com/erusev/parsedown/
[2]:https://github.com/Nessworthy/parsedown-extension/blob/master/src/ParsedownInlineExtension.php
[3]:https://github.com/Nessworthy/parsedown-extension/blob/master/src/ParsedownBlockExtension.php