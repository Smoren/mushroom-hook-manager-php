# Mushroom Hook Manager
Composer plugin for running post-install/post-update scripts of your project's dependencies

## Using in your package
Possible example:

Create file `MushroomHooks.php` in the root of you package source directory with such content:

```
<?php

namespace Your\Package\Namespace;

class MushroomHooks
{
    public static function afterInstall($params)
    {
        // some actions on after install your package...
    }

    public static function afterUpdate($params)
    {
        // some actions on after update your package...
    }
}
```

Next add this lines to your package's `composer.json` file:

```
...
"require": {
    "smoren/mushroom-hook-manager": "1.0.0",
    ...
},
...
"extra": {
    ...
    "mushroom-use-hooks": true,
    "mushroom-hooks": {
        "after-install": [
            "Your\\Package\\Namespace\\MushroomHooks::afterInstall"
        ],
        "after-update": [
            "Your\\Package\\Namespace\\MushroomHooks::afterUpdate"
        ]
    }
}
...
```

You can also pass some params to package's hooks in your project
(as arguments for methods `afterInstall` and `afterUpdate`).

Example for your project's`composer.json` file:

```
...
"extra": {
    ...
    "mushroom-hooks-params": {
        "your-composer/package-name": {
            "after-install": {
                "some-param": 1,
                "another-param": 2
            },
            "after-update": {
                "foo": "bar"
            }
        }
    }
}
...
```