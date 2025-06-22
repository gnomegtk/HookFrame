<?php
// classes/HandlerInterface.php

namespace Classes;

interface HandlerInterface
{
    /**
     * Set the next handler in the chain.
     *
     * @param HandlerInterface $handler
     * @return HandlerInterface
     */
    public function setNextHandler(HandlerInterface $handler): HandlerInterface;

    /**
     * Handle a decoded envelope.
     *
     * @param array $envelope
     */
    public function handle(array $envelope): void;
}
