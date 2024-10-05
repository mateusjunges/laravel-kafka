---
title: Kafka fake
weight: 1
---

When testing your application, you may wish to "mock" certain aspects of the app, so they are not actually executed during a given test.
This package provides convenient helpers for mocking the kafka producer out of the box. These helpers primarily provide a convenience layer over Mockery
so you don't have to manually make complicated Mockery method calls.

The Kafka facade also provides methods to perform assertions over published messages, such as `assertPublished`, `assertPublishedOn` and `assertNothingPublished`.

