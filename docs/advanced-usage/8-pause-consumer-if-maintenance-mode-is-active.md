---
title: Pause consumer if maintenance mode is active
weight: 8
---

Consumer pauses gracefully if application goes to maintenance mode.
So if application goes to maintenance mode by `php artisan down`, the consumer 
will stop consuming after the current message gracefully. It will continue 
to consume if maintenance mode is deactivated.
