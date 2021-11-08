# Changelog

All relevant changes to `mateusjunges/laravel-kafka` will be documented here.

## [2021-11-08 v1.2.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.3...v1.2.0)
### Added
- Added the security protocol to Sasl class. By default, its used `SASL_PLAINTEXT`([#f4e62d2](https://github.com/mateusjunges/laravel-kafka/commit/f4e62d2d5e8d2842ccd3168295245a911f5f74fb))
- Allow usage of SASL with Kafka producers ([#04686cc](https://github.com/mateusjunges/laravel-kafka/commit/04686ccef5a423427ab8b8ba294fff830a880802))
- Allow both `SASL_PLAINTEXT` and `SASL_SSL` security protocols with sasl. ([#49e1112](https://github.com/mateusjunges/laravel-kafka/commit/49e1112a2edd1ca9c02e476ac8d4c4d7d1220ef2))

## [2021-11-05 v1.1.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.2...v1.1.3)
### Added
- Allow usage of custom options for producer config ([[#38ca04](https://github.com/mateusjunges/laravel-kafka/commit/38ca04c15b1feea10c33e9865377f712a1809d40)]) 

## [2021-10-20 v1.1.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.1...v1.1.2)
### Added
- Added validation to ensure a kafka consumer will not subscribe to a topic if it is already subscribed. ([#f1ab25c](https://github.com/mateusjunges/laravel-kafka/commit/f1ab25c))

### Changed
- Make `$topics` parameter optional on `Kafka::createConsumer` method. ([#ef7a1a8](https://github.com/mateusjunges/laravel-kafka/commit/ef7a1a8))

## [2021-09-28 v1.1.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.0...v1.1.1)
### Fixed
- Fixed documentation about message handlers ([#c375e10](https://github.com/mateusjunges/laravel-kafka/commit/c375e100b416f63837bcc9be5762c1762772050a))
- Fixed tests to test a message can be consumed using message handlers

## [2021-09-27 v1.1.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.0.2...v1.1.0)
### Added
- Added option to use custom serializers/deserializers with Kafka([#5](https://github.com/mateusjunges/laravel-kafka/pull/5))
- Added default AVRO, Json and Null serializers/deserializers ([#5](https://github.com/mateusjunges/laravel-kafka/pull/5))
- Message handlers now receives a `Junges\Kafka\Contracts\KafkaConsumerMessage` instance, instead of `RdKafka\Message` directly.

### Changed
- `Junges\Kafka\Message` class namespace changed to `Junges\Kafka\Message\Message`
- Method `withMessageKey` renamed to `withBodyKey`, on `Junges\Kafka\Message\Message` and `Junges\Kafka\Producers\ProducerBuilder` classes. ([b41c310](https://github.com/mateusjunges/laravel-kafka/pull/5/commits/b41c310f5e4acb8a09500ddc222456642b8787da))


## [2021-09-25 v1.0.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.0.1...v1.0.2)
### Added 
- Add documentation to the config file
- Add tag to publish config file

### Fixed
- Fix documentation typos [#2](https://github.com/mateusjunges/laravel-kafka/pull/2)
- Fix installation docs

## [2021-09-12 v1.0.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.0.0...v1.0.1)
- Fixed argument for `assertPublished`, used to perform assertions over published messages
- Add testing documentation

## 2021-09-12 v1.0.0
- Initial release
