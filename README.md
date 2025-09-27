<p align="center">
  <a href="https;//newtron.app" target="_blank">
    <img src="https://raw.githubusercontent.com/newtron-framework/assets/master/newtron-logo.png" alt="newtron" />
  </a>
</p>

> This is the core framework package for Newtron.

## About Newtron

Newtron is a PHP framework designed around the principle of complex features with embarrassingly simple code. It's built to solve real developer pain points by making common, complex tasks trivially simple:

- Automatic query optimization
- Progressive enhancement by default
- Embarrassingly simple forms
- Shared hosting friendly

## Current Status

Newtron is currently a work in progress. In it's current state, Newtron can support simple static sites.

### ✅ Phase 1 Complete: Core foundation

- Application container with dependency injection
- Request/Response abstraction
- Two routing modes (declarative or file-based)
- Middleware pipeline
- Quark, a custom templating engine
- Error handling and logging

### 🚧 Up Next: Developer experience improvements and the signature forms system

## Requirements

- PHP 8.3 or higher
- Composer

## Installation

This is the core framework package. For new projects, use the starter template:

```bash
composer create-project newtron/app my-app
```

To use this core package in your own project:

```bash
composer require newtron/core
```

## Documentation

WIP

## Quick Example

Newtron expects the following directories in its root path:

```bash
├── config/
├── routes/
└── templates/
```

```php
<?php

use Newtron\Core\Application\App;
use Newtron\Core\Http\Request;
use Newtron\Core\Http\Response;
use Newtron\Core\Routing\Route;

// Initialize the application
$app = App::create($rootPath);

// Declarative routing
Route::get('/hello/{name}', function($name) {
  return Response::create("Hello, {$name}!");
});

// Or use file-based routing (hello.[name].php)
// Just set the routing mode in config/routing.php:
// return [
//   'mode' => 'file'
// ];

// Run the application (handle the request)
App::run();
```

## Architecture
Newtron Core provides the foundational components:

### Application Container

- Dependency injection with auto-resolution
- Service provider system for organizing registrations
- Environment detection and configuration

### HTTP Layer

- Clean Request/Response abstractions
- Built on PHP superglobals (no external dependencies)
- Support for JSON, form data, file uploads
- Middleware pipeline

### Routing

- Declarative: `Route::get('/users/{id}', UserController::class)`
- File-based: Create `routes/users/[id].php` and extend `FileRoute` - that's it
- Route caching and optimization
- Parameter binding

### Quark Templates

- Lightweight, powerful templating
- Layout inheritance and named slots
- Designed for tight integration with Newtron features
- Support custom filters and directives

## Philosophy

Every feature in Newtron follows these principles:

- Complex features should require embarrassingly simple code
- Progressive enhancement should be automatic, not optional
- Performance optimization should happen behind the scenes
- Deployment should work everywhere with minimal configuration

## Contributing

Newtron is in active development. All contributions that align with the core philosophy of making complex things simple are welcome.
