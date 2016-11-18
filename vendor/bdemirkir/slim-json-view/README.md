# Slim Framework JSON View

This is a very simple Slim Framework view helper for JSON API's. You can use this component to create simple JSON responses in your Slim Framework application.

## Install

Via [Composer](https://getcomposer.org/)

```bash
$ composer require bdemirkir/slim-json-view
```

Requires Slim Framework 3 and PHP 5.5.0 or newer.

## Usage

```php
// Create Slim app
$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register JSON View helper
$container['view'] = function ($c) {
    return new \Slim\Views\JSON();
};

// Successful response
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $this->view->render($response, [
        'name' => $args['name']
    ]);
});

// Unauthorized response
$app->get('/unauthorized/{name}', function ($request, $response, $args) {
    return $this->view->render($response, [
        'name' => $args['name']
    ], 401);
});

// Run app
$app->run();
```


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
