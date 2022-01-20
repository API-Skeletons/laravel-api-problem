# API Problem for Laravel

[![Build Status](https://github.com/API-Skeletons/laravel-api-problem/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/API-Skeletons/laravel-api-problem/actions/workflows/continuous-integration.yml?query=branch%3Amain)
[![Code Coverage](https://codecov.io/gh/API-Skeletons/laravel-api-problem/branch/main/graphs/badge.svg)](https://codecov.io/gh/API-Skeletons/laravel-api-problem/branch/main)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2b-blue)](https://img.shields.io/badge/PHP-8.0%2b-blue)
[![Total Downloads](https://poser.pugx.org/api-skeletons/laravel-api-problem/downloads)](//packagist.org/packages/api-skeletons/laravel-api-problem)
[![License](https://poser.pugx.org/api-skeletons/laravel-api-problem/license)](//packagist.org/packages/api-skeletons/laravel-api-problem)

This repository implements [RFC 7807](https://www.rfc-editor.org/rfc/rfc7807.html)
"Problem Details for HTTP APIs" for Laravel.

## Installation

Run the following to install this library using [Composer](https://getcomposer.org/):

```bash
composer require api-skeletons/laravel-api-problem
```

## Quick Start

```php
use ApiSkeletons\Laravel\ApiProblem\Facades\ApiProblem;

return ApiProblem::response('Detailed Unauthorized Message', 401);
```

This will result in a 401 response with header

```shell
Content-Type: application/problem+json
```

and content
```json
{
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "title": "Unauthorized",
    "status": 401,
    "detail": "Detailed Unauthorized Messsge"
}
```

## Use

### Using the facade

You may use the ApiProblem object in two ways.  First, you can use the facade to
return a response quickly and directly as shown in the Quick Start.  When using
the facade the arguments to the `response()` method are:

```php
response(
    string|Throwable $detail, 
    int|string $status, 
    ?string $type = null, 
    ?string $title = null, 
    array $additional = []
)
```

### Creating an object

When creating an ApiProblem object directly, the first two parameters are swapped.
The reason for this is the constructor for the original object remains unchanged
and the `response()` function is modified to match the standard
[Laravel response](https://laravel.com/docs/8.x/responses#response-objects)
format.

```php
__construct(
    int|string $status, 
    string|Throwable $detail, 
    ?string $type = null, 
    ?string $title = null, 
    array $additional = []
)
```

An example of creating an object directly:

```php
use ApiSkeletons\Laravel\ApiProblem\ApiProblem;

$apiProblem = new ApiProblem(401, 'Detailed Unauthorized Message');
return $apiProblem->response();
```

## Additional Details

The 5th parameter to ApiProblem is $additional.  This array adds adhoc properties to the
JSON response.  One method of using this array is a 422 response with details of the problem:

```php
use ApiSkeletons\Laravel\ApiProblem\Facades\ApiProblem;
use Illuminate\Validation\ValidationException;

try {
    $validated = $request->validate([
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
    ]);
} catch (\Illuminate\Validation\ValidationException $e) {
    return ApiProblem::response($e->getMessage(), 422, null, null, ['errors' => $e->errors()]);
}
```

## Attribution

The bulk of this repository was copied from Laminas API Tools.  I wanted to provide a
simplified interface specific to Laravel.  Though the tool could have been used directly
from the Laminas library it would have come with a lot of overhead.
