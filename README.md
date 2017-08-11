# TNTSearch

Makes use of [TNT Studio](https://github.com/teamtnt)'s [TNTSearch](https://github.com/teamtnt/tntsearch).


## Installation

 * In the root of your Bolt project, do:
```
composer require teamtnt/tntsearch
```
 * Make sure `app/config/extensions` is write-able.
 * Install this extension via the Extensions page in Bolt
 * Go to Extensions » TNTSearch and click on *Index*



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
