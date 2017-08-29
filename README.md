# TNTSearch

Makes use of [TNT Studio](https://github.com/teamtnt)'s [TNTSearch](https://github.com/teamtnt/tntsearch).


## Installation

 * In the root of your Bolt project, do:
```
composer require teamtnt/tntsearch:^1.0
```
 * Make sure `app/config/extensions` is write-able.
 * Install this extension via the Extensions page in Bolt
 * Go to Extensions » TNTSearch and click on *Index*


## Configuration

[...]


## Options

Here are some general options for searching when using this extension.

*Standard*:
- `contenttypes` : An array with contenttypes, leave empty or undefined to search through all contenttypes.
- `type`         : The type of search to perform. Choose from `'default'|'fuzzy'|'boolean'`.

*Pagination*:
- `pageNumber` : The page number, index starts at 1 <sup>1</sup>.
- `pageSize`   : The number of items per page.

*Fuzzy*, only applies when setting `type` to `'fuzzy'`.
- `prefixLength`  : ...
- `maxExpansions` : ...
- `distance`      : The levenshtein distance.

<sup>1</sup> the reason pages start at 1 is because end users tend to say the first page instead of the zero-th page. Otherwise zero-indexed is preferred.


## Twig

```
{% set records = tntsearch('<query>', {
    contenttypes  : 'pages',
    type          : 'default',
    pageNumber    : 1,
    pageSize      : 10,
    prefixLength  : 2,
    maxExpansions : 50,
    distance      : 2
}) %}
```


## Nut

```
tntsearch:index
tntsearch:search
```




## Issues

### Issue: Pagination

There is no pagination in TNTSearch, so we need to do:

```
$limit = $pageSize * $pageNumber;
$offset = $pageSize * ($pageNumber - 1);
```

Then split the array from `$offset` until the end of the results.


### Issue: Taxonomies, Relationships, Repeaters and Custom Fields.

Ugh!


### Issue: Searching in all contenttypes

There is no search over multiple tables in TNTSearch. So we can circumvent this
by making an extra lookup table and a complex join query.


### Issue: Searching in multiple contenttypes

This might be a feature for later. Because if we use the same solution as above,
we are going to need too many additional indices.





------

[...] shorthand functions??

{% set records = tntsearchContenttype(query, contenttype) %}
{% set records = tntsearchContenttypeFuzzy(query, contenttype) %}
{% set records = tntsearchContenttypeBoolean(query, contenttype) %}

{% set records = tntsearchAll(query) %}
{% set records = tntsearchAllFuzzy(query) %}
{% set records = tntsearchAllBoolean(query) %}

{% set records = tntsearchFuzzy(query) %}
{% set records = tntsearchFuzzyBoolean(query) %}

[...] pagination, etc.













---
Everything below here is just thinking aloud mode... and a lot of this is going to change.

## Twig Functions

(WIP)

```
{% set records = tntsearch(query, contenttype, options) %}
{% set records = tntsearchBoolean(query, contenttype, options) %}
{% set records = tntsearchFuzzy(query, contenttype, options) %}

...
{% set records = tntsearchAll(query, options) %} ?????
```

- `contenttype`, the contenttype you want to search in
- `query`, the term you want to search for
- `options`:
    - ''

## Nut commands

### Index

- `tntsearch:index`                        - indexes all contenttypes
- `tntsearch:index <contenttype>`          - indexes a single given contenttype

### Search

(WIP)

- `tntsearch:search <query>`               - searches for `<query>` in all contenttypes
- `tntsearch:search <query> <contenttype>` - searches for `<query>` in a single contenttype

- `--limit` or `-l`: limit the number of results



#### Fuzzy Search

Same as above, add `--fuzzy` or `-f`. The following options are available to tweak the results:
  - `--fuzzy`          or `-f`: Enable fuzzy search (`fuzziness`)
  - `--prefix_length`  or `-p`: (`fuzzy_prefix_length`)
  - `--max_expansions` or `-m`: (`fuzzy_max_expansions`)
  - `--distance`       or `-d`: The levenshtein distance (`fuzzy_distance`)

### Boolean Search

- `tntsearch:boolean <query>`               - boolean search for `<query>` in all contenttypes
- `tntsearch:boolean <query> <contenttype>` - boolean search for `<query>` in a single contenttype

## References

- https://github.com/teamtnt/tntsearch
