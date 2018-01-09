[![Latest Stable Version](https://poser.pugx.org/chris-kruining/utilities/v/stable)](https://packagist.org/packages/chris-kruining/utilities)
[![Latest Unstable Version](https://poser.pugx.org/chris-kruining/utilities/v/unstable)](https://packagist.org/packages/chris-kruining/utilities)
[![License](https://poser.pugx.org/chris-kruining/utilities/license)](https://packagist.org/packages/chris-kruining/utilities)
[![Build Status](https://travis-ci.org/chris-kruining/utilities.svg?branch=master)](https://travis-ci.org/chris-kruining/utilities)
[![Maintainability](https://api.codeclimate.com/v1/badges/107d203ddf629c8f2f8f/maintainability)](https://codeclimate.com/github/chris-kruining/utilities/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/107d203ddf629c8f2f8f/test_coverage)](https://codeclimate.com/github/chris-kruining/utilities/test_coverage)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/14a870b9-f364-4030-971b-048cbe19cdd5/mini.png)](https://insight.sensiolabs.com/projects/14a870b9-f364-4030-971b-048cbe19cdd5)

# Utilities
some utilities for php, nothing special, nothing new, just to my taste

# Installation
installation is simply done via composer
`composer require chris-kruining/utilities`

# Usage

The main component of the library is the `Collection` at the moment. The goal of the `Collection` is to provide a object oriented interface for [array functions](http://nl1.php.net/manual/en/ref.array.php). It also has a couple of extra's like method chaining and linq-esc implementation(NOT DONE YET) so that you may interact with the `Collection` as if it was a database table

for example
```php
CPB\Utilities\Collections\Collection::from([ 'these', 'are', null, null, 'some', null, 'test', 'values', null ])
  ->filter()
  ->toString(' ');
```

would yield
```php
'these are some test values'
```

which is the same as
```php
join(' ', array_filter([ 'these', 'are', null, null, 'some', null, 'test', 'values', null ]));
```

I agree, the vannilla way is shorter now but the strength really comes into play when we start adding callbacks and increase the chain length
```php
CPB\Utilities\Collections\Collection::from([ 'these', '', '', 'are', null, 'some', null, 'test', '', 'values', '' ])
  ->filter(function($v){ return $v !== null && strlen($v) > 0; })
  ->map(function($k, $v){ return $k . '::' . $v; })
  ->toString('|');
```

would yield
```php
'0_these|1_are|2_some|3_test|4_values'
```

which is the same as
```php
$filtered = array_filter(
  [ 'these', '', '', 'are', null, 'some', null, 'test', '', 'values', '' ], 
  function($v){ return $v !== null && strlen($v) > 0; }
);

join('|', array_map(function($k, $v){ return $k . '::' . $v; }, array_keys($filtered), $filtered));
```

As you can see the collection version maintains readability whereas the vannilla version loses in my opinion it's charm because to achie a single goal you need to spread it out over multiple variables

# The future

What I hope to accomplish is querying over a collection
```php
$collction['max($ID), $Name, $City where $Name startswith "Chris" limit 3, 5']
```

# Roadmap

- [X] Implement basic features to `Collection`
- [X] Bloat `Collection` with features :P
- [X] Split of features into an inheritance tree
- [X] Split lazy mode from `Collection` into `LazyCollection` and implement PHP's array functions as generators
- [ ] Finish inheritance structure
- [ ] Implement `LazyCollection`
- [ ] Look into the possibility of turning the lib into an PHP extension with PHP-CPP (simply to improve performance)
- [ ] Write an SQL syntax parser to allow querying over `Queryable`
- [X] (Better) implement the `Queryable` interface in a new class so the `Collection` doesn't become bloated 
