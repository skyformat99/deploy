<?php

namespace App\Action;

use App\Action\AbstractAction;
use App\Action\ActionInterface;
use App\Action\Traits\IsInitialiseStage;
use App\Builder;
use Ronanchilvers\Foundation\Config;

/**
 * Action to manage any shared locations for a project
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class SharedAction extends AbstractAction implements ActionInterface
{
    /**
     * @see App\Action\ActionInterface::run()
     */
    public function run(Config $configuration, Context $context)
    {
    }
}
