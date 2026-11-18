<?php

declare(strict_types=1);

namespace CraftAiObserver\Model;

/**
 * The provider and model an event was produced with, when known.
 */
final readonly class Model
{
    public function __construct(
        public ?string $provider = null,
        public ?string $model = null
    ) {
    }

    /**
     * @return array{provider: string|null, model: string|null}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model
        ];
    }
}
