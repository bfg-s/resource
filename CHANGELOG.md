# Changelog
All notable changes to this project will be documented in this file.

## [1.0.5] - 2021-11-06
## Added
- Ability to expand.
- `only` fields, for getting need fields.
- `create` method that allows you to create resources that will determine on your own, a collection or a single instance.

## [1.0.4] - 2021-11-03
### Added
- Accounting nesting.
- Helper nesting methods: `isPrent`, `isChild`, `isNesting`, `nesting`

## [1.0.0] - 2021-09-24
### Added
- `EloquentForPageScopeTrait` - Eloquent for page scope.
- `EloquentLatestScopeTrait` - Eloquent latest scope.
- `EloquentLimitScopeTrait` - Eloquent limit scope.
- `EloquentOrderByScopeTrait` - Eloquent order by (+ `order_by_desc`) scope.
- `EloquentRandomScopeTrait` - Eloquent random order scope.
- `EloquentSkipScopeTrait` - Eloquent skip scope.
- The concept of scope collection (Scope which is performed on the clean result of the eloquent collection).
- The concept of request method finish (GET - `getMethod($result)`, POST - `postMethod($result)`...). Only if the result remained the builder.
- `getMethod` for `EloquentAllScopeTrait`.
- `optionsMethod` for `EloquentFirstScopeTrait`.
- `CanUser` for scope.
### Changed
- Scope parameters concept.

## [0.0.3 - Beta] - 2021-09-20
### Added
- Personal resource collection class.
### Changed
- Renamed `Get` `scope` to `All` scope for javascript SDK (Otherwise, the API request in some cases will not look very good.)

## [0.0.2 - Beta] - 2021-09-20
### Added
- `CanResource` attribute for policy check.
- `PHP` Class scope API 
- Scope name in `UndefinedScopeExeption` message.

## [0.0.1 - Beta] - 2021-09-19
### Added
- License mit.
- Readme documentation file.
- Initial functionality of the package.
