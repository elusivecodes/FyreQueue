# FyreQueue

**FyreQueue** is a free, open-source queue library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Methods](#methods)
- [Queues](#queues)
    - [Redis](#redis)
- [Workers](#workers)
- [Listeners](#listeners)
- [Messages](#messages)



## Installation

**Using Composer**

```
composer require fyre/queue
```

In PHP:

```php
use Fyre\Queue\QueueManager;
```


## Methods

**Clear**

Clear all instances and configs.

```php
QueueManager::clear();
```

**Get Config**

Set a [*Queue*](#queues) config.

- `$key` is a string representing the [*Queue*](#queues) key.

```php
$config = QueueManager::getConfig($key);
```

Alternatively, if the `$key` argument is omitted an array containing all configurations will be returned.

```php
$config = QueueManager::getConfig();
```

**Get Key**

Get the key for a [*Queue*](#queues) instance.

- `$queue` is a [*Queue*](#queues).

```php
$key = QueueManager::getKey($queue);
```

**Has Config**

Check if a [*Queue*](#queues) config exists.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$hasConfig = QueueManager::hasConfig($key);
```

**Is Loaded**

Check if a [*Queue*](#queues) instance is loaded.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$isLoaded = QueueManager::isLoaded($key);
```

**Load**

Load a [*Queue*](#queues).

- `$options` is an array containing configuration options.

```php
$queue = QueueManager::load($options);
```

**Push**

Push a job to a [*Queue*](#queues).

- `$className` is a string representing the job class.
- `$arguments` is an array containing arguments that will be passed to the job.
- `$options` is an array containing options for the [*Message*](#messages).
    - `config` is a string representing the configuration key, and will default to "*default*".
    - `queue` is a string representing the [*Queue*](#queues) name, and will default to "*default*".
    - `method` is a string representing the class method, and will default to "*run*".
    - `delay` is a number representing the number of seconds before the job should run, and will default to *0*.
    - `expires` is a number representing the number of seconds after which the job will expire, and will default to *0*.

```php
QueueManager::push($className, $arguments, $options);
```

**Set Config**

Set the [*Queue*](#queues) config.

- `$key` is a string representing the [*Queue*](#queues) key.
- `$options` is an array containing configuration options.

```php
QueueManager::setConfig($key, $options);
```

Alternatively, a single array can be provided containing key/value of configuration options.

```php
QueueManager::setConfig($config);
```

**Unload**

Unload a [*Queue*](#queues).

- `$key` is a string representing the [*Queue*](#queues) key, and will default to `QueueManager::DEFAULT`.

```php
$unloaded = QueueManager::unload($key);
```

**Use**

Load a shared [*Queue*](#queues) instance.

- `$key` is a string representing the [*Queue*](#queues) key, and will default to *"default"*.

```php
$queue = QueueManager::use($key);
```


## Queues

You can load a specific queue by specifying the `className` option of the `$options` variable above.

Custom queues can be created by extending `\Fyre\Queue\Queue`, ensuring all below methods are implemented.

**Clear**

Clear all items from the queue.

- `$queueName` is a string representing the queue name.

```php
$queue->clear($queueName);
```

**Get Listener**

Get the queue [*Listener*](#listeners).

```php
$queue->getListener();
```

**Pop**

Pop the last item off the queue.

- `$queueName` is a string representing the queue name.

```php
$message = $queue->pop($queueName);
```

**Push**

Push an item onto the queue.

- `$queueName` is a string representing the queue name.
- `$message` is a [*Message*](#message).

```php
$queue->push($queueName, $message);
```


### Redis

The Redis queue can be loaded using custom configuration.

- `$key` is a string representing the queue key.
- `$options` is an array containing configuration options.
    - `className` must be set to `\Fyre\Queue\Handlers\RedisQueue`.
    - `listener` is a string representing the [*Listener*](#listeners) class, and will default to `\Fyre\Queue\Listener`.
    - `host` is a string representing the Redis host, and will default to "*127.0.0.1*".
    - `password` is a string representing the Redis password
    - `port` is a number indicating the Redis port, and will default to *6379*.
    - `database` is a string representing the Redis database.
    - `timeout` is a number indicating the connection timeout.

```php
QueueManager::setConfig($key, $options);

$queue = QueueManager::use($key);
```


## Workers

Workers are long running tasks that will consume and execute jobs from the queue.

```php
use Fyre\Queue\Worker;
```

- `$options` is an array containing configuration options.
    - `config` is a string representing the configuration key, and will default to "*default*".
    - `queue` is a string representing the queue name, and will default to "*default*".
    - `maxJobs` is a number representing the maximum number of jobs to execute, and will default to *0*.
    - `maxRuntime` is a number representing the maximum number of seconds the worker should run, and will default to *0*.

```php
$worker = new Worker($options);
```

**Run**

Run the worker.

```php
$worker->run();
```


## Listeners

You can use a specific listener by specifying the `listener` option of the `$options` variable above.

Custom listener can be created by extending `\Fyre\Queue\Listener`, ensuring all below methods are implemented.

**Exception**

Handle a message exception.

- `$message` is the [*Message*](#messages).
- `$exception` is the exception.

```php
$listener->exception($message, $exception);
```

**Failure**

Handle a failed message.

- `$message` is the [*Message*](#messages).

```php
$listener->failure($message);
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
    - `config` is a string representing the configuration key, and will default to "*default*".
    - `queue` is a string representing the queue name, and will default to "*default*".
    - `method` is a string representing the class method, and will default to "*run*".
    - `delay` is a number representing the number of seconds before the job should run, and will default to *0*.
    - `expires` is a number representing the number of seconds after which the job will expire, and will default to *0*.

```php
$message = new Message($options);
```

**Get Arguments**

Get the message arguments.

```php
$arguments = $message->getArguments();
```

**Get Callback**

Get the message callback.

```php
$callback = $message->getCallback();
```

**Get Config**

Get the message config.

```php
$config = $message->getConfig();
```

**Is Expired**

Determine if the message has expired.

```php
$isExpired = $message->isExpired();
```

**Is Ready**

Determine if the message is ready.

```php
$isReady = $message->isReady();
```

**Is Valid**

Determine if the message is valid.

```php
$isValid = $message->isValid();
```