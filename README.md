# OliveWeb
Modular PHP Microframework

## Installation
1. Drop in anywhere in your Apache htdocs or IIS www heirarchy. Both a .htaccess file and web.config is included.
2. Create your page under pages/ (nested directories allowed). Name it <pagename>.page.php
3. At the top of your page, make sure to verify the PHP constant INPROCESS is defined. This ensures your page is loaded via OliveWeb and not directly.
4. Link your page in pages/routing.php

## phpDocumentor Docs
Docs are in /docs/index.html

To generate, run phpdocumentor from the root directory. (Requires phpdocumentor is installed on your machine.)

## Page Routing
In pages/routing.php, you can specify what static URL's link to what pages as well as dynamic URL routing with regular expressions.

For static routing, add an entry to $routing_literal. For instance, if you want `<base url>/foo/bar` to route to `pages/barfoo.page.php`, you would add:
`"foo/bar" => "barfoo"`

For dynamic routing, add an entry to $routing_dynamic. For instance, if you wanted `<base url>/users/<numeric user ID>/friends` to route to `pages/users/friends.page.php`, you would add:
  `"users\/([0-9]+)\/friends" => "users/friends"`
  
## Common Functions
`Olive::baseURL()`: Returns the base URL of your site.

`Olive::requestParam(id)`: Returns the request parameter from the URL. For instance, `/users/<numeric user ID>/friends` would have the request parameter ID's 0, 1, and 2 respectively.

`Olive::redirect(url, remote)`: Redirects the user to another webpage.

See the phpDocumentor Docs for more information.

## Modules
Modules are on-demand loaded singletons containing reusable and portable code. An example of a module would be a templating engine or a wrapper to a hashing library.

To add a module, drop it in the modules/ directory.

To access a module named Foo, first get the instance of the Modules registry: `$modules = Modules::getInstance()` then get the instance of the Foo module: `$foo = $modules['Foo']`. You can now call a method bar: `$foo->bar()`
