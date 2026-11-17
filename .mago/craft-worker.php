<?php

declare(strict_types=1);

use CraftAiObserver\Dev\Mago\CraftAiObserverExtension;
use Mago\Sdk\Worker;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(
    CraftAiObserverExtension::create(),
))->run();
