## Example of directory structure and usage

Assuming you have file structure like this (for a theme)

```
core
  |- init-container.php
  |- config.php
src
  |- AssetsProvider.php
  |- Maintanance.php
  |- Options.php
  |- functions.php
.phpstorm.meta.php
composer.json
functions.php
readme.txt
style.css

... (other theme files)
```

Example of `composer.json`

```json
{
  "name": "your-vendor/your-theme-name",
  "type": "project",
  "autoload": {
    "psr-4": {
      "YourVendor\\YourProduct\\": "src/"
    }
  },
  "require": {
    "php": ">=7.2",
    "wpshop/container": "^1.0"
  }
}
```

Create specific function at file `src/functions.php`

```php
<?php

namespace YourVendor\YourProduct;

use WPShop\Container\Psr11\Container;

/**
 * @return Container
 */
function theme_container() 
{
    static $container;
    if (!$container) {
        $init = require_once dirname(__DIR__) . '/init-container.php';
        $config = require_once dirname(__DIR__) . '/config.php';
        $container = new Container($init($config));
    }
    return $container;
}
 ```

Content of `init-container.php`

```php
<?php

use WPShop\Container;
use YourVendor\YourProduct\Maintanance;
use YourVendor\YourProduct\Options;

return function ($config) {
    $container = new Container\ServiceRegistry([
        AssetsProvider::class => function($c) {
            return new AssetsProvider($c[Maintanance::class]);
        },
        Maintanance::class => function($c) {
            return new Maintanance($c[Options::class]);
        },
        Options::class => function () use ($config) {
            return new Options($config);
        }
    ]);
    
    do_action('your_vendor/your_product/init_container', $container);
    
    return $container;
}


```

Content of `.phpstorm.meta.php` for auto-suggest

```php
<?php
/**
 * @see https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 */

namespace PHPSTORM_META {
	override( \Psr\Container\ContainerInterface::get( 0 ), map( [

	] ) );
}

 ```

After that there will be ability to use container anywhere in your project, for example in main theme
file `functions.php`

```php
<?php

use YourVendor\YourProduct\AssetsProvider;
use function YourVendor\YourProduct\theme_container;

require __DIR__ . '/vendor/autoload.php';

theme_container()->get(AssetsProvider::class)->init();
theme_container()->get(Maintanance::class)->init();

```
