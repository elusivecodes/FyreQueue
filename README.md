# FyreQueue

**FyreQueue** is a free, open-source queue library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Queues](#queues)
    - [Redis](#redis)
- [Workers](#workers)
- [Listeners](#listeners)
- [Messages](#messages)
- [Commands](#commands)
    - [Stats](#stats)
    - [Worker](#worker)



## Installation

**Using Composer**

```
composer require fyre/queue
```

In PHP:

```php
use Fyre\Queue\QueueManager;
```

## Basic Usage

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$config` is a [*Config*](https://github.com/elusivecodes/FyreConfig).

```php
$queueManager = new QueueManager($container, $config);
```

Default configuration options will be resolved from the "*Queue*" key in the [*Config*](https://github.com/elusivecodes/FyreConfig).

**Autoloading**

It is recommended to bind the *QueueManager* to the [*Container*](https://github.com/elusivecodes/FyreContainer) as a singleton.

```php
$container->singleton(QueueManager::class);
```

Any dependencies will be injected automatically when loading from the [*Container*](https://github.com/elusivecodes/FyreContainer).

```php
$queueManager = $container->use(QueueManager::class);
```


## Methods

**Build**

Build a [*Queue*](#queues).

- `$options` is an array containing configuration options.

```php
$queue = $queueManager->build($options);
```

[*Queue*](#queues) dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).

**Clear**

Clear all instances and configs.

```php
$queueManager->clear();
```

**Get Config**

Set a [*Queue*](#queues) config.

- `$key` is a string representing the [*Queue*](#queues) key.

```php
$config = $queueManager->getConfig($key);
```

Alternatively, if the `$key` argument is omitted an array containing all configurations will be returned.

```php
$config = $queueManager->getConfig();
```

**Has Config**

Determine whether a [*Queue*](#queues) config exists.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$hasConfig = $queueManager->hasConfig($key);
```

**Is Loaded**

Determine whether a [*Queue*](#queues) instance is loaded.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$isLoaded = $queueManager->isLoaded($key);
```

**Push**

Push a job to a [*Queue*](#queues).

- `$className` is a string representing the job class.
- `$arguments` is an array containing arguments that will be passed to the job.
- `$options` is an array containing options for the [*Message*](#messages).
    - `config` is a string representing the configuration key, and will default to `QueueManager::DEFAULT`.
    - `queue` is a string representing the [queue](#queues) name, and will default to `QueueManager::DEFAULT`.
    - `method` is a string representing the class method, and will default to "*run*".
    - `delay` is a number representing the number of seconds before the job should run, and will default to *0*.
    - `expires` is a number representing the number of seconds after which the job will expire, and will default to *0*.
    - `retry` is a boolean indicating whether the job should be retried if it fails, and will default to *true*.
    - `maxRetries` is a number indicating the maximum number of times the job should be retried, and will default to *5*.
    - `unique` is a boolean indicating whether the job should be unique, and will default to *false*.

```php
$queueManager->push($className, $arguments, $options);
```

Job dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).

**Set Config**

Set the [*Queue*](#queues) config.

- `$key` is a string representing the [*Queue*](#queues) key.
- `$options` is an array containing configuration options.

```php
$queueManager->setConfig($key, $options);
```

**Unload**

Unload a [*Queue*](#queues).

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$queueManager->unload($key);
```

**Use**

Load a shared [*Queue*](#queues) instance.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to *"default"*.

```php
$queue = $queueManager->use($key);
```

[*Queue*](#queues) dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).


## Queues

You can load a specific queue by specifying the `className` option of the `$options` variable above.

Custom queues can be created by extending `\Fyre\Queue\Queue`, ensuring all below methods are implemented.

**Clear**

Clear all items from the queue.

- `$queueName` is a string representing the queue name, and will default to `QueueManager::DEFAULT`.

```php
$queue->clear($queueName);
```

**Complete**

Mark a job as completed.

- `$message` is a [*Message*](#message).

```php
$queue->complete($message);
```

**Fail**

Mark a job as failed.

- `$message` is a [*Message*](#message).

```php
$queue->fail($message);
```

**Get Listeners**

Get the queue [*listeners*](#listeners).

```php
$listeners - $queue->getListeners();
```

**Pop**

Pop the last item off the queue.

- `$queueName` is a string representing the queue name.

```php
$message = $queue->pop($queueName);
```

**Push**

Push a job onto the queue.

- `$message` is a [*Message*](#message).

```php
$queue->push($message);
```

**Queues**

Get all the active queues.

```php
$queues = $queue->queues();
```

**Reset**

Reset the queue statistics.

- `$queueName` is a string representing the queue name, and will default to `QueueManager::DEFAULT`.

```php
$queue->reset($queueName);
```

**Stats**

Get the statistics for a queue.

- `$queueName` is a string representing the queue name, and will default to `QueueManager::DEFAULT`.

```php
$stats = $queue->stats($queueName);
```


### Redis

The Redis queue can be loaded using custom configuration.

- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Queue\Handlers\RedisQueue`.
    - `listeners` is an array containing [*Listener*](#listeners) class names or objects, and will default to *[]*.
    - `host` is a string representing the Redis host, and will default to "*127.0.0.1*".
    - `password` is a string representing the Redis password
    - `port` is a number indicating the Redis port, and will default to *6379*.
    - `database` is a string representing the Redis database.
    - `timeout` is a number indicating the connection timeout.

```php
$container->use(Config::class)->set('Queue.redis', $options);
```


## Workers

Workers are long running tasks that will consume and execute jobs from the queue.

```php
use Fyre\Queue\Worker;
```

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$queueManager` is a *QueueManager*.
- `$options` is an array containing configuration options.
    - `config` is a string representing the configuration key, and will default to `QueueManager::DEFAULT`.
    - `queue` is a string representing the queue name, and will default to `QueueManager::DEFAULT`.
    - `maxJobs` is a number representing the maximum number of jobs to execute, and will default to *0*.
    - `maxRuntime` is a number representing the maximum number of seconds the worker should run, and will default to *0*.
    - `rest` is a number representing the number of microseconds to rest after processing a job, and will default to *10000*.
    - `sleep` is a number representing the number of microseconds to sleep if no jobs are in the queue, and will default to *100000*.

```php
$worker = new Worker($container, $queueManager, $options);
```

**Run**

Run the worker.

```php
$worker->run();
```


## Listeners

You can attach listeners to a [*Queue*](#queues) by specifying a `listeners` array in the `$options` variable above.

Listener dependencies will be resolved automatically from the [*Container*](https://github.com/elusivecodes/FyreContainer).

Custom listener can be created and implement any of the below methods.

**Exception**

Handle a message exception.

- `$message` is the [*Message*](#messages).
- `$exception` is the exception.`
- `$retried` is a boolean indicating whether the message was retried.

```php
$listener->exception($message, $exception, $retried);
```

**Failure**

Handle a failed message.

- `$message` is the [*Message*](#messages).
- `$retried` is a boolean indicating whether the message was retried.

```php
$listener->failure($message, $retried);
```

**Invalid**

Handle an invalid message.

- `$message` is the [*Message*](#messages).

```php
$listener->invalid($message);
```

**Start**

Handle a start message.

- `$message` is the [*Message*](#messages).

```php
$listener->start($message);
```

**Success**

Handle a success message.

- `$message` is the [*Message*](#messages).

```php
$listener->success($message);
```


## Messages

Messages are used internally to pass data between the [*Queue*](#queues), [*Worker*](#workers) and [*Listener*](#listeners).

```php
use Fyre\Queue\Message;
```

- `$options` is an array containing options for the message.
    - `className` is a string representing the job class.
    - `arguments` is an array containing arguments that will be passed to the job.
    - `config` is a string representing the configuration key, and will default to `QueueManager::DEFAULT`.
    - `queue` is a string representing the queue name, and will default to `QueueManager::DEFAULT`.
    - `method` is a string representing the class method, and will default to "*run*".
    - `delay` is a number representing the number of seconds before the job should run, and will default to *0*.
    - `expires` is a number representing the number of seconds after which the job will expire, and will default to *0*.
    - `retry` is a boolean indicating whether the job should be retried if it fails, and will default to *true*.
    - `maxRetries` is a number indicating the maximum number of times the job should be retried, and will default to *5*.
    - `unique` is a boolean indicating whether the job should be unique, and will default to *false*.

```php
$message = new Message($options);
```

**Get After**

Get the timestamp when the message can be sent.

```php
$after = $message->getTimestamp();
```

**Get Config**

Get the message config.

```php
$config = $message->getConfig();
```

**Get Hash**

Get the message hash.

```php
$hash = $message->getHash();
```

**Get Queue**

Get the message queue.

```php
$queueName = $message->getQueue();
```

**Is Expired**

Determine whether the message has expired.

```php
$isExpired = $message->isExpired();
```

**Is Ready**

Determine whether the message is ready.

```php
$isReady = $message->isReady();
```

**Is Unique**

Determine whether the message is unique.

```php
$isUnique = $message->isUnique();
```

**Is Valid**

Determine whether the message is valid.

```php
$isValid = $message->isValid();
```

**Should Retry**

Determine whether the message should be retried.

```php
$shouldretry = $message->shouldRetry();
```


## Commands

### Stats

Display stats for the queue.

- `--config` is a the configuration key, and will default to `QueueManager::DEFAULT`.
- `--queue` is a the [queue](#queues) name, and will default to `QueueManager::DEFAULT`.

```php
$commandRunner->run('queue:stats', ['--config', 'default', '--queue', 'default']);
```

### Worker

Start a background queue worker.

- `--config` is a the configuration key, and will default to `QueueManager::DEFAULT`.
- `--queue` is a the [queue](#queues) name, and will default to `QueueManager::DEFAULT`.
- `--max-jobs` is the maximum number of jobs to execute, and will default to *0*.
- `--max-runtime` is the maximum number of seconds the worker should run, and will default to *0*.

```php
$commandRunner->run('queue:worker', ['--config', 'default', '--queue', 'default', '--max-jobs', '99', '--max-runtime', '60']);
```