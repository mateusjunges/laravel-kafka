# Changelog

All relevant changes to `mateusjunges/laravel-kafka` will be documented here.

##[2025-02-21 v2.5.0](https://github.com/mateusjunges/laravel-kafka/compare/v2.4.2...v2.5.0)
* Add support for Laravel 12 by [@mateusjunges](https://github.com/mateusjunges) in [#332](https://github.com/mateusjunges/laravel-kafka/pull/332)

##[2024-12-19 v2.4.2](https://github.com/mateusjunges/laravel-kafka/compare/v2.4.1...v2.4.2)
* Update dependencies by [@mateusjunges](https://github.com/mateusjunges) in [`161361d`](https://github.com/mateusjunges/laravel-kafka/commit/161361d588a2d9cdbfda7f1b4c1cc6849f857cd6)

##[2024-11-22 v2.4.1](https://github.com/mateusjunges/laravel-kafka/compare/v2.4.0...v2.4.1)
* Allow to customize flush settings (retries + timeout) by @sash in [#317](https://github.com/mateusjunges/laravel-kafka/pull/317)

##[2024-11-04 v2.4.0](https://github.com/mateusjunges/laravel-kafka/compare/v2.3.2...v2.4.0)
* Allow to use other types of Keys other than strings by @mateusjunges in [#322](https://github.com/mateusjunges/laravel-kafka/pull/322)

##[2024-11-04 v2.3.2](https://github.com/mateusjunges/laravel-kafka/compare/v2.3.1...v2.3.2)
* Prefer new Laravel 11 Cache key by @cragonnyunt in [#321](https://github.com/mateusjunges/laravel-kafka/pull/321)

##[2024-10-14 v2.3.1](https://github.com/mateusjunges/laravel-kafka/compare/v2.3.0...v2.3.1)
* Convert `KAFKA_CONSUMER_DEFAULT_TIMEOUT` to an integer to avoid type error by @niuf416 in [#230](https://github.com/mateusjunges/laravel-kafka/pull/320)

##[2024-10-06 v2.3.0](https://github.com/mateusjunges/laravel-kafka/compare/v2.2.0...v2.3.0)
* Add message id key name to config by @LabbeAramis in [#315](https://github.com/mateusjunges/laravel-kafka/pull/315)
* Adds in the ability to set complete body when producing messages by @sash in [#316](https://github.com/mateusjunges/laravel-kafka/pull/316)


##[2024-08-18 v2.2.0](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.5...v2.2.0)
* Introduce async publishing by @mateusjunges and @sash in [#312](https://github.com/mateusjunges/laravel-kafka/pull/312)
* Allow to reset the Kafka Manager by @mateusjunges in [#314](https://github.com/mateusjunges/laravel-kafka/pull/314)

##[2024-08-17 v2.1.5](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.4...v2.1.5)
* Do not override topic when publishing batch messages by @mateusjunges in [#313](https://github.com/mateusjunges/laravel-kafka/pull/313)

##[2024-08-05 v2.1.4](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.3...v2.1.4)
* Fix consumer middlewares not working as expected when using the `$consumer` parameter in message handlers by @sash in [#304](https://github.com/mateusjunges/laravel-kafka/pull/304)

##[2024-08-02 v2.1.3](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.2...v2.1.3)
* Attempt to fix invalid group id generation by @mateusjunges in [#305](https://github.com/mateusjunges/laravel-kafka/pull/305)

##[2024-07-19 v2.1.2](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.1...v2.1.2)
* Add int casting to getLastRestart method by @glioympas in [#298](https://github.com/mateusjunges/laravel-kafka/pull/298)

## [2024-05-14 v2.1.1](https://github.com/mateusjunges/laravel-kafka/compare/v2.1.0...v2.1.1)
* Allow null payloads when sending the message to DLQ

## [2024-05-14 v2.1.0](https://github.com/mateusjunges/laravel-kafka/compare/v2.0.4...v2.1.0)
* Properly shut down the producer for single messages. Improve docs for sending single and multiple messages by @mateusjunges on [#283](https://github.com/mateusjunges/laravel-kafka/pull/283)

## [2024-05-11 v2.0.4](https://github.com/mateusjunges/laravel-kafka/compare/v2.0.3...v2.0.4)
* Replace `resolve` with `app` by @SachinBahukhandi in [#280](https://github.com/mateusjunges/laravel-kafka/pull/280)

## [2024-04-29 v2.0.3](https://github.com/mateusjunges/laravel-kafka/compare/v2.0.2...v2.0.3)
* Mirror logic to enable fake builder set the topic name for all the messages at once by @cvairlis in [#279](https://github.com/mateusjunges/laravel-kafka/pull/279)

## [2024-03-12 v2.0.2](https://github.com/mateusjunges/laravel-kafka/compare/v2.0.1...v2.0.2)
* Fix type error caused by wrong type on `commit` parameter by @panaiteandreisilviu on [#276](https://github.com/mateusjunges/laravel-kafka/pull/276)

## [2024-03-13 v2.0.1](https://github.com/mateusjunges/laravel-kafka/compare/v2.0.0...v2.0.1)
* Do not require to specify message topic twice by @mateusjunges in [#270](https://github.com/mateusjunges/laravel-kafka/pull/270)

## [2024-03-12 v2.0.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.x...v2.0.0)
* Allow to queue message handlers by @mateusjunges in [#177](https://github.com/mateusjunges/laravel-kafka/pull/177)
* Add missing return types by @mateusjunges in [#189](https://github.com/mateusjunges/laravel-kafka/pull/189)
* Upgrade to PHP 8.1 and make code more maintainable by @mateusjunges in [#161](https://github.com/mateusjunges/laravel-kafka/pull/161)
* Allow to define **before** callbacks by @ebrahimradi in [#192](https://github.com/mateusjunges/laravel-kafka/pull/192)
* Add publishing/consuming events by @mateusjunges in [#193](https://github.com/mateusjunges/laravel-kafka/pull/193)
* Send throwable info to DLQ by @mateusjunges in [#194](https://github.com/mateusjunges/laravel-kafka/pull/194)
* Add the ability to use Kafka transactional producers by @mateusjunges in [#223](https://github.com/mateusjunges/laravel-kafka/pull/223)
* Partition assignment by @mateusjunges in [#234](https://github.com/mateusjunges/laravel-kafka/pull/234)
* Document subscribing to topics using regex by @mateusjunges in [#239](https://github.com/mateusjunges/laravel-kafka/pull/239)
* Passthru missing methods on `KafkaFake` for macro accessibility by @mateusjunges in [#246]https://github.com/mateusjunges/laravel-kafka/pull/246 and (https://github.com/mateusjunges/laravel-kafka/pull/250)
* Allow to uniquely identify messages by @mateusjunges in [#244](https://github.com/mateusjunges/laravel-kafka/pull/244)
* Update CI to run without docker on Github CI by @mateusjunges in [#254](https://github.com/mateusjunges/laravel-kafka/pull/254)
* Drop support for Laravel 9 by @mateusjunges in [#255](https://github.com/mateusjunges/laravel-kafka/pull/255)
* Add support for Laravel 11 by @mateusjunges in [#257](https://github.com/mateusjunges/laravel-kafka/pull/257)
* Improve producer performance by reducing `flush` calls by @mateusjunges in [#252](https://github.com/mateusjunges/laravel-kafka/pull/252)
* Allow to test macroed consumers by @mateusjunges in [#267](https://github.com/mateusjunges/laravel-kafka/pull/267)
 

## [2024-01-23 v1.13.9](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.8...v1.13.9)
* Install a signal handler for SIGINT (cmd/ctrl + c) by @mateusjunges

## [2024-01-09 v1.13.8](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.7...v1.13.8)
* Fix install issues when using laravel v1.13.x by @mateusjunges

## [2024-01-09 v1.13.7](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.6...v1.13.7)
* Add timeout feature for consumers by @mihaileu in [#233](https://github.com/mateusjunges/laravel-kafka/pull/233)

## [2023-12-07 v1.13.6](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.5...v1.13.6)
* Remove internal annotation from interface by @mateusjunges

## [2023-10-24 v1.13.5](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.4...v1.13.5)
* Fixed default securityProtocol config by @SergkeiM in [#215](https://github.com/mateusjunges/laravel-kafka/pull/215)

## [2023-09-24 v1.13.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.3...v1.13.4)
* Make Kafka macroable by @lentex in [#221](https://github.com/mateusjunges/laravel-kafka/pull/221)

## [2023-09-18 v1.13.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.2...v1.13.3)
* Update package dependencies by @mateusjunges
* Update README.md to link to the 1.13 documentation page by @tavsec in [#212](https://github.com/mateusjunges/laravel-kafka/pull/212)

## [2023-04-12 v1.13.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.1...v1.13.2)
* Fix auto commit config by @ebrahimradi in [#203](https://github.com/mateusjunges/laravel-kafka/pull/203)

## [2023-04-08 v1.13.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.13.0...v1.13.1)
* Add the ability to change the cache driver by @BBonnet22 in [#199](https://github.com/mateusjunges/laravel-kafka/pull/199)

## [2023-03-28 v1.13.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.12.3...v1.13.0)
- Fix consumer auto commit creating wrong committer by @mateusjunges on [#186](https://github.com/mateusjunges/laravel-kafka/pull/186)

## [2023-03-04 v1.12.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.12.1...v1.12.2)
- Fix `assertPublishedOnTimes` to be used with batch messages - Fixes [#179](https://github.com/mateusjunges/laravel-kafka/issues/179) by @mateusjunges on [#180](https://github.com/mateusjunges/laravel-kafka/pull/180)

## [2023-02-21 v1.12.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.12.1...v1.12.2)
- Fix contracts marked as internal (Fixes [#178](https://github.com/mateusjunges/laravel-kafka/issues/178))

## [2023-02-15 v1.12.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.12.0...v1.12.1)
- Drop support for older versions of `rdkafka` by @mateusjunges
- Fix bug when sending message to DLQ (allow to send message key) by @ebrahimradi in [#175](https://github.com/mateusjunges/laravel-kafka/pull/175)
- Allow to send message headers to DLQ by @mateusjunges


## [2023-02-09 v1.12.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.11.0...v1.12.0)
- Allow consumers to subscribe to `SIGQUIT` and `SIGTERM` signals by @mateusjunges on [#172](https://github.com/mateusjunges/laravel-kafka/pull/172)
- Add `onStopConsume` method to the Consumer class

## [2023-01-30 v1.11.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.10.2...v1.11.0)
- Add support for Laravel 10 by @mateusjunges and @mihaileu on [#171](https://github.com/mateusjunges/laravel-kafka/pull/171)

## [2023-01-24 v1.10.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.10.1...v1.10.2)
- Report consumer exceptions by @behzadev on [#169](https://github.com/mateusjunges/laravel-kafka/pull/169)

## [2023-01-10 v1.10.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.10.0...v1.10.1)
- Add Logger contract to allow users to implement their own Logger by @remarkusable in [#165](https://github.com/mateusjunges/laravel-kafka/pull/165)

## [2022-12-17 v1.10.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.9.3...v1.10.0)
- Added support for PHP 8.2 by @mateusjunges in [#159](https://github.com/mateusjunges/laravel-kafka/pull/159)
- Dropped support for `ext-rdkafka` v4.0 by @mateusjunges in [#158](https://github.com/mateusjunges/laravel-kafka/pull/158)
- Dropped support for Laravel 8 by @mateusjunges in [#158](https://github.com/mateusjunges/laravel-kafka/pull/158)
- Dropped support for PHP 8.0 by @mateusjunges in [#159](https://github.com/mateusjunges/laravel-kafka/pull/159)

## [2022-11-29 v1.9.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.9.2...v1.9.3)
- Fixes `kafka.php` config file

## [2022-11-29 v1.9.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.9.1...v1.9.2)
- Allow to configure sleep timeout when Producer is retrying `flush` by @mateusjunges in [#156](https://github.com/mateusjunges/laravel-kafka/pull/156)

## [2022-10-28 v1.9.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.9.0...v1.9.1)
- Filter config options for consumer and producers by @mateusjunges in [#151](https://github.com/mateusjunges/laravel-kafka/pull/151)

## [2022-10-28 v1.9.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.9...v1.9.0)
- Make Kafka class usable through interface injection by @mosharaf13 in [#150](https://github.com/mateusjunges/laravel-kafka/pull/150)

## [2022-10-04 v1.8.8](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.8...v1.8.9)
- Fixes batch message processing by @mateusjunges in [#143](https://github.com/mateusjunges/laravel-kafka/pull/143)

## [2022-09-12 v1.8.8](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.7...v1.8.8)
- Fix docs by @mateusjunges in [#137](https://github.com/mateusjunges/laravel-kafka/pull/137)
- Add methods to configure config callbacks by @mateusjunges in [#136](https://github.com/mateusjunges/laravel-kafka/pull/136)
- Allows to customize the Deserializer in `kafka:consume` Command  by @cragonnyunt in [#140](https://github.com/mateusjunges/laravel-kafka/pull/140)

## [2022-08-22 v1.8.7](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.6...v1.8.7)
## Fixes
- Make consumer timeout configurable (fixes #132) by @mateusjunges in [#134](https://github.com/mateusjunges/laravel-kafka/pull/134)

## [2022-08-17 v1.8.6](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.5...v1.8.6)
## Fixes
- Fixes [#126](https://github.com/mateusjunges/laravel-kafka/issues/126) using stub files, with [#129](https://github.com/mateusjunges/laravel-kafka/pull/129) by @smortexa

## [2022-08-16 v1.8.5](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.4...v1.8.5)
## Fixes
- Fixes [#126](https://github.com/mateusjunges/laravel-kafka/issues/126) with [#128](https://github.com/mateusjunges/laravel-kafka/pull/128) by @mateusjunges
- Add Restart command in [#119](https://github.com/mateusjunges/laravel-kafka/pull/119) by @gasoju


## [2022-08-02 v1.8.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.3...v1.8.4)
## Fixes
- Fixes [#113](https://github.com/mateusjunges/laravel-kafka/issues/113) with [#123](https://github.com/mateusjunges/laravel-kafka/pull/123) by @mateusjunges

## [2022-08-02 v1.8.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.2...v1.8.3)
## Fixes
- Fixes [#120](https://github.com/mateusjunges/laravel-kafka/issues/120) with [#122](https://github.com/mateusjunges/laravel-kafka/pull/122) by @mateusjunges 


## [2022-07-21 v1.8.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.1...v1.8.2)
### Added 
- Resolve consumer instance using service container, by @cragonnyunt in [#118](https://github.com/mateusjunges/laravel-kafka/pull/118)

## [2022-06-13 v1.8.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.8.0...v1.8.1)
### Added 
- Improved exception handling when a call to `flush` returns an error, by @mateusjunges in [#112](https://github.com/mateusjunges/laravel-kafka/pull/112)

## [2022-06-13 v1.8.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.7...v1.8.0)
### Added
- Message consumer Mock, by @gajosu in [#107](https://github.com/mateusjunges/laravel-kafka/pull/107)
- Add Batch Support for Message consumer Mock, by @gajosu in [#109](https://github.com/mateusjunges/laravel-kafka/pull/109)
- Added consumer contracts, by @mateusjunges in [#110](https://github.com/mateusjunges/laravel-kafka/pull/110)
- Add docs for mocking consumers, by @mateusjunges in [#111](https://github.com/mateusjunges/laravel-kafka/pull/111)
- Additional option to stop consumer after last message on topic, by @StounhandJ in [#103](https://github.com/mateusjunges/laravel-kafka/pull/103)

### Fixed
- Fix docker file by @gajosu in [#108](https://github.com/mateusjunges/laravel-kafka/pull/108)

## [2022-06-03 v1.7.7](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.6...v1.7.7)
### Fixed
- Fixes Sasl security protocol not being passed to config class. By @gajosu in [#106](https://github.com/mateusjunges/laravel-kafka/pull/106)
- Link to the current version documentation fixed by @Elnadrion in [#104](https://github.com/mateusjunges/laravel-kafka/pull/104)
- (Documentation) Added missing comas in the class params by @shanginn in [#101](https://github.com/mateusjunges/laravel-kafka/pull/101)

## [2022-05-18 v1.7.6](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.5...v1.7.6)
- Fixed Passing null to parameter #1 ($string) of type string is deprecated by @elnadrion on [#100](https://github.com/mateusjunges/laravel-kafka/pull/100)

## [2022-05-01 v1.7.5](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.4...v1.7.5)
### Fixed
- Fixes error when using SASL_SSL with KafkaConsumerCommmand (issue [#96](https://github.com/mateusjunges/laravel-kafka/issues/96), fixed with [#3ea902d](https://github.com/mateusjunges/laravel-kafka/commit/3ea902dbc1a44130395ecbd37ef892b24580e91e))

## [2022-04-19 v1.7.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.3...v1.7.4)
### Fixed
- Fix undefined offset 0 when trying to set Dead Letter Queues without subscribing to any kafka topics ([#e06849c](https://github.com/mateusjunges/laravel-kafka/commit/e06849c13be412a42206b8931d1afc0d7d5ae155))

## [2022-04-19 v1.7.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.2...v1.7.3)
### Fixed
- Fixed Kafka Facade docblock on [#93](https://github.com/mateusjunges/laravel-kafka/pull/93) by @nmfzone

## [2022-04-19 v1.6.6](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.5...v1.6.6)
### Fixed
- Fixed Kafka Facade docblock on [#93](https://github.com/mateusjunges/laravel-kafka/pull/93) by @nmfzone

## [2022-04-07 v1.7.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.1...v1.7.2)
### Fixed
- Fix Json Serialize to not serialize the same message twice on [#92](https://github.com/mateusjunges/laravel-kafka/pull/92) by @lukecurtis93

## [2022-02-28 v1.7.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.7.0...v1.7.1)
### Added
- Added support for batch producing and handling batch of messages by @vsvp21 in [#86](https://github.com/mateusjunges/laravel-kafka/pull/86)

## [2022-02-28 v1.7.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.4...v1.7.0)
### Fixed
- Return callback result for published messages filter when callback is provided on [#87](https://github.com/mateusjunges/laravel-kafka/pull/87) by @nmfzone

### Added
- Added support for Laravel 9 in the tests pipeline on [#88](https://github.com/mateusjunges/laravel-kafka/pull/88) by @mateusjunges

## [2022-044-07 v1.6.5](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.4...v1.6.5)
### Fixed
- Fix Json Serialize to not serialize the same message twice on [#92](https://github.com/mateusjunges/laravel-kafka/pull/92) by @lukecurtis93

## [2022-02-28 v1.6.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.3...v1.6.4)
### Fixed
- Use correct consumer group id config key in consumer command

## [2022-02-16 v1.6.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.2...v1.6.3)
### Fixed
- Added missing `auto.offset.reset` to the consumer only options array.

## [2022-02-16 v1.6.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.1...v1.6.2)
### Fixed
- Fixes CONFWARN on consumer on [#75 (issue)](https://github.com/mateusjunges/laravel-kafka/issues/75) with [#76 (pull request)](https://github.com/mateusjunges/laravel-kafka/pull/76)
- Fixes Sasl authentication not working [#77 (issue)](https://github.com/mateusjunges/laravel-kafka/issues/77) with [#78 (pull request)](https://github.com/mateusjunges/laravel-kafka/pull/78)

## [2022-01-20 v1.6.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.6.0...v1.6.1)
### Fixed
- Fixes [#69](https://github.com/mateusjunges/laravel-kafka/issues/69)

## [2022-01-10 v1.6.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.5.3...v1.6.0)
### Added
- Add support for `ext-rdkafka` v6.0

### Fixed
- Changed docker image used for tests to [`mateusjunges/laravel`](https://github.com/mateusjunges/laravel-docker)

### Changed
- Removed Null Serializer
- Message headers can't be null ([#ea9d97f](https://github.com/mateusjunges/laravel-kafka/commit/ea9d97f))

## [2022-01-21 v1.5.4](https://github.com/mateusjunges/laravel-kafka/compare/v1.5.3...v1.5.4)
### Fixed
- Allow using sasl with lowercase config keys ([#1cc7521](https://github.com/mateusjunges/laravel-kafka/commit/1cc75211e96c80de04fbca0784fbe28c4e69ab25))

## [2021-12-21 v1.5.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.5.2...v1.5.3)
### Fixed
- Included SASL on consumer config when applicable ([#5c028bf](https://github.com/mateusjunges/laravel-kafka/commit/5c028bfd3f6588e411babe5429fa78dc89ed2a22))

## [2021-12-19 v1.5.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.5.1...v1.5.2)
### Fixed
- Fixed built in consumer command ([#50](https://github.com/mateusjunges/laravel-kafka/pull/50))

## [2021-12-10 v1.5.1](https://github.com/mateusjunges/laravel-kafka/compare/v1.5.0...v1.5.1)
### Added
- Added a `withBrokers` setter to the consumer api, allowing to set brokers on the run ([#6a639ce](https://github.com/mateusjunges/laravel-kafka/commit/6a639ce3670ef1df25c79d11923fcb13b37d4f8f))
- Added boolean argument to `withAutoCommit`, which defaults to true ([#3ffb226](https://github.com/mateusjunges/laravel-kafka/commit/3ffb2265b0abd46e2d7048f55f9982bfedd441e2))
- Added support for `librdkafka` v0.11.3 ([#4600fdc](https://github.com/mateusjunges/laravel-kafka/commit/4600fdc0097420130751b16b06e6877b02712d07))

### Fixed
- Cast `auto_commit` to string on initial consumer options ([#f2a6c2b](https://github.com/mateusjunges/laravel-kafka/commit/f2a6c2b30d1180347f423df18132a90024c7b542))

## [2021-12-08 v1.5.0](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.5...v1.5.0)
### Changed
- Renamed `AvroencoderException` to `AvroSerializerException` by @rtuin in [#38](https://github.com/mateusjunges/laravel-kafka/pull/38)
- Extend all exceptions from `LaravelKafkaException` by @rtuin in [#38](https://github.com/mateusjunges/laravel-kafka/pull/38)

## [2021-12-08 v1.4.5](https://github.com/mateusjunges/laravel-kafka/compare/v1.4.4...v1.4.5)
### Added
- Added message context to committers plus allow to use custom committers ([#133a4bb](https://github.com/mateusjunges/laravel-kafka/commit/133a4bb446c773d16a4fd05b0e4cb25900d45550))
- Added retryable handler, allowing to retry message handling and block processing any other message. ([#0f9aeee](https://github.com/mateusjunges/laravel-kafka/commit/0f9aeee67fe4fd776a812d5a5ebc74dff2bcf2b6))

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

## [2022-01-20 v1.3.3](https://github.com/mateusjunges/laravel-kafka/compare/v1.3.2...v1.3.3)
### Fixed 
- Allow using SASL with lowercase config keys. ([#ca542e21](https://github.com/mateusjunges/laravel-kafka/commit/ca542e21ee085659f33c3bf2b39329fe06e42274))

## [2021-11-25 v1.3.2](https://github.com/mateusjunges/laravel-kafka/compare/v1.3.0...v1.3.1)
### Fixed
- Fix incorrect message published count ([#06c3844](https://github.com/mateusjunges/laravel-kafka/commit/06c3844))
- Fixed exception thrown when kafka cannot complete a flush call ([#9a1fcba](https://github.com/mateusjunges/laravel-kafka/commit/9a1fcbace9b549e54c9a2c17174c74478f87d47e))

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
