<?php

namespace Payer\Checkout\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

/**
 * Dummy Command
 *
 * @package Payer_Checkout
 * @module  Payer
 * @author  Webbhuset <info@webbhuset.se>
 */
class DummyCommand implements CommandInterface
{
    /**
     * Does nothing.
     */
    public function execute(array $commandSubject)
    {
    }
}
