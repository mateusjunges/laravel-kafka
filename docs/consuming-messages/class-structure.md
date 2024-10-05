---
title: Class structure
weight: 9
---

Consumer classes are very simple, and it is basically a Laravel Command class. To get started, let's take a look at an example consumer.

```php
<?php

namespace App\Console\Commands\Consumers;

use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\KafkaConsumerMessage;

class MyTopicConsumer extends Command
{
    protected $signature = "consume:my-topic";

    protected $description = "Consume Kafka messages from 'my-topic'."

    public function handle()
    {
        $consumer = Kafka::createConsumer(['my-topic'])
            ->withBrokers('localhost:8092')
            ->withAutoCommit()
            ->withHandler(function(KafkaConsumerMessage $message) {
                // Handle your message here
            })
            ->build();
            
            $consumer->consume();
    }
}
```

Now, to keep this consumer process running permanently in the background, you should use a process monitor such as [supervisor](http://supervisord.org/) to ensure that the consumer does not stop running.

## Supervisor configuration
In production, you need a way to keep your consumer processes running. For this reason, you need to configure a process monitor that can detect when your consumer processes exit and automatically restart them. In addition, process monitors can allow you to specify how many consumer processes you would like to run concurrently. Supervisor is a process monitor commonly used in Linux environments and we will discuss how to configure it in the following documentation.

### Installing supervisor
To install supervisor on Ubuntu, you may use the following command:
```bash
sudo apt-get install supervisor
```

### Configuring supervisor
Supervisor configuration files are typically stored in the `/etc/supervisor/conf.d` directory. Within this directory, you may create any number of configuration files that instruct supervisor how your processes should be monitored. For example, let's create a `my-topic-consumer.conf` file that starts and monitors our Consumer:

```text
[program:my-topic-consumer]
directory=/var/www/html
process_name=%(program_name)s_%(process_num)02d
command=php artisan consume:my-topic
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor-laravel-worker.log
stopwaitsecs=3600
```
#### Starting Supervisor
Onnce the configuration file has been created, you may update Supervisor configuration and start the processes using the following commands:

```bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start my-topic-consumer:*
```
