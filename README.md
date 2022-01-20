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

return ApiProblem::response(401, 'Detailed Unauthorized Message');
```

## Attribution

The bulk of this repository was copied from Laminas API Tools.  I wanted to provide a
simplified interface specific to Laravel.  Though the tool could have been used directly
from the Laminas library it would have come with a lot of overhead.  Thanks Laminas.
