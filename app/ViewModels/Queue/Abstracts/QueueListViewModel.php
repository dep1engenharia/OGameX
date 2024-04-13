<?php

namespace OGame\ViewModels\Queue\Abstracts;

class QueueListViewModel
{
    /**
     * List of queue items.
     *
     * @var array<QueueViewModel>
     */
    public array $queue;

    /**
     * Constructor.
     *
     * @param array<QueueViewModel> $queue
     */
    public function __construct(array $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Get amount of items in the queue.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->queue);
    }

    /**
     * Get amount of items in the queue.
     *
     * @return bool
     */
    public function isQueueFull(): bool
    {
        // Max items in queue is 4.
        // TODO: refactor into global/constant setting configurable by admin.
        $maxItemsInQueue = 4;
        return count($this->queue) >= $maxItemsInQueue;
    }
}