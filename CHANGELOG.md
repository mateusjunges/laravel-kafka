# Changelog

All relevant changes to `mateusjunges/laravel-kafka` will be documented here.

## [2021-12-03 v1.4.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.3...v1.4.4)
### Fixed
- Fixed composer.json dependencies. Improve installation process ([#5981907](https://github.com/mateusjunges/laravel-kafka/commit/598190772ac1cca28f72d7de73468b7cecf21113))

## [2021-12-01 v1.4.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.2...v1.4.3)
### Added
- Allow `createConsumer` to use consumer group id from config ([#559b467](https://github.com/mateusjunges/laravel-kafka/commit/559b467b5ff0cb47e5002ea37ec5dda9f7d88d1a))
- Improve consistency with serializers/deserializers naming ([#b55772a](https://github.com/mateusjunges/laravel-kafka/commit/b55772a2e91f44a1c2809380c725e4e9af738912))

## [2021-11-29 v1.4.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.1...v1.4.2)
### Added
- Added `stopConsume` method to allow consumer to be gracefully stopped ([#db37381](https://github.com/mateusjunges/laravel-kafka/commit/db37381bcc9e8903e476fedd13d6962ca20597ad))
- Reduced consumer timeout to a more realistic number, this allows signals to be caught every 2 seconds allowing graceful shutdown ([#af1902f](https://github.com/mateusjunges/laravel-kafka/commit/af1902f480286ca31ab1f973a56ee66edfa8b994))

## [2020-11-23 v1.4.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.0...v1.4.1)
### Fixed
- Fixes exception thrown when kafka cannot complete a `flush` call. ([#ddae8e3](https://github.com/mateusjunges/laravel-kafka/commit/ddae8e3167fd180017ae6d0f15039f8600552f00))

## [2021-11-23 v1.4.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.3.1...v1.4.0)
### Fixed
- Reworked testing framework to properly check what was dispatched ([#ec8b3f6](https://github.com/mateusjunges/laravel-kafka/commit/ec8b3f61998a0d85723b1b7457c76ac3fffda345)) 
- Fixed incorrect param ordering on test ([#1022799](https://github.com/mateusjunges/laravel-kafka/commit/10227992b055ea745a29f13015c3f2bbff5d8687))
- Fixed KafkaFake to store published messages correctly ([#4fe6e96](https://github.com/mateusjunges/laravel-kafka/commit/4fe6e96ab253eab88e0b50233e939a3bbf16e385))
- Added tests to ensure count of published messages works ([#7ea370f](https://github.com/mateusjunges/laravel-kafka/commit/7ea370f150c9bf122d67b1e4f4b3e1750ef7f7fa))

## [2021-11-14 v1.3.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.3.0...v1.3.1)
### Changed
- Fix `assertPublishedOnTimes` to allow usage of callback if the message is null. ([#c5b496](https://github.com/mateusjunges/laravel-kafka/commit/c5b496cf5c7b50e1519e9b7726cff8d2aaf3fda1))

## [2021-11-14 v1.3.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.2.0...v1.3.0)
### Added
- Added `assertPublishedTimes` and `assertPublishedOnTimes` methods ([#a16a10d](https://github.com/mateusjunges/laravel-kafka/commit/a16a10dbe9ddfbbe5e412148b2083faead774cf8))

### Changed
- Make topic name optional. Add method to set topic name when using fake driver ([#12dde5](https://github.com/mateusjunges/laravel-kafka/commit/12dde5de2aa8b9d735fa3fb093dc48948733f3a3), [#8f5b25](https://github.com/mateusjunges/laravel-kafka/commit/8f5b258609cbd6f475aee4e9a9517daeffcd5b60), [#f3c8b43](https://github.com/mateusjunges/laravel-kafka/commit/f3c8b4392872063060891bc9f2152712b639e81b), [#fe19922](https://github.com/mateusjunges/laravel-kafka/commit/fe199227fd1b657660d30fb7fc0cca41d5a4d24f))
- Make broker parameter optional ([#5625bef](https://github.com/mateusjunges/laravel-kafka/commit/5625befca0c11ae19b109f1368d42d5aaa284da2), [#aa5596c](https://github.com/mateusjunges/laravel-kafka/commit/aa5596c21910bae780e9f4be8afb5864a6b8eab7), [#c6ad0e9](https://github.com/mateusjunges/laravel-kafka/commit/c6ad0e98ee65536d30a09bdaeb89c3e184860f07), [#5117cd8](https://github.com/mateusjunges/laravel-kafka/commit/5117cd8eeb435ab1774144cca1ef6ed36b0d09d7))
- Allow getTopicName to return null on KafkaMessage contract ([#3e6289d](https://github.com/mateusjunges/laravel-kafka/commit/3e6289d91cf36bdc185eb142810b1dffe463df6f))
- Make topicName optional on Message::create() ([#7213af9](https://github.com/mateusjunges/laravel-kafka/commit/7213af9b6fc843301d8ffc84e961387d118fde37))
- Fix `publishOn` and `createConsumer` method signatures on kafka facade ([#eb66e8e](https://github.com/mateusjunges/laravel-kafka/commit/eb66e8efa1a94be020193017dd9ea2f1025c41e9))
- Make message argument optional for `assertPublished` and `assertPublishedOn` methods ([#9ec5eea](https://github.com/mateusjunges/laravel-kafka/commit/9ec5eeabc3bf816211d25bde44354999aa6410df))

## [2021-11-08 v1.2.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.3...v1.2.0)
### Added
- Added the security protocol to Sasl class. By default, its used `SASL_PLAINTEXT`([#f4e62d2](https://github.com/mateusjunges/laravel-kafka/commit/f4e62d2d5e8d2842ccd3168295245a911f5f74fb))
- Allow usage of SASL with Kafka producers ([#04686cc](https://github.com/mateusjunges/laravel-kafka/commit/04686ccef5a423427ab8b8ba294fff830a880802))
- Allow both `SASL_PLAINTEXT` and `SASL_SSL` security protocols with sasl. ([#49e1112](https://github.com/mateusjunges/laravel-kafka/commit/49e1112a2edd1ca9c02e476ac8d4c4d7d1220ef2))

## [2021-11-05 v1.1.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.1.2...v1.1.3)
### Added
- Allow usage of custom options for producer config ([#38ca04](https://github.com/mateusjunges/laravel-kafka/commit/38ca04c15b1feea10c33e9865377f712a1809d40)) 

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
