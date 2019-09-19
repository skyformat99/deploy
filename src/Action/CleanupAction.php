<?php

namespace App\Action;

use App\Action\AbstractAction;
use App\Action\ActionInterface;
use App\Action\Context;
use App\Facades\Log;
use App\Model\Deployment;
use Ronanchilvers\Foundation\Config;
use Ronanchilvers\Orm\Orm;
use Ronanchilvers\Utility\File;

/**
 * Action to clean up old deployments after deployment
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class CleanupAction extends AbstractAction implements ActionInterface
{
    /**
     * @see App\Action\ActionInterface::run()
     */
    public function run(Config $configuration, Context $context)
    {
        $thisDeployment    = $context->getOrThrow('deployment', 'Invalid or missing deployment');
        $deploymentBaseDir = $context->getOrThrow('deployment_base_dir', 'Invalid or missing deployment dir');
        $project           = $context->getOrThrow('project', 'Invalid or missing project');
        $number            = $configuration->get('cleanup.keep_deployments', 5);
        $deployments       = Orm::finder(Deployment::class)->earlierThan(
            $project,
            $number
        );
        $count = count($deployments);
        Log::debug(sprintf("Found %d deployments to clean", $count));
        if (0 == $count) {
            return;
        }
        foreach ($deployments as $deployment) {
            $deploymentDir = File::join($deploymentBaseDir, $deployment->id);
            Log::error('Cleaning old deployment', [
                'deployment_dir' => $deploymentDir,
            ]);
            if (!File::rm($deploymentDir)) {
                $this->error(
                    $thisDeployment,
                    'Unable to remove deployment folder for deployment ' . $deployment->number,
                    [
                        "Deployment Folder - " . $deploymentDir
                    ]
                );
                Log::error('Unable to remove old deployment directory', [
                    'deployment_dir' => $deploymentDir,
                ]);
            }
            if (!$deployment->delete()) {
                $this->error(
                    $thisDeployment,
                    'Unable to remove database entry for deployment ' . $deployment->number
                );
                Log::error('Unable to remove old deployment', [
                    'deployment' => $deployment->toArray(),
                ]);
            }
            $this->info(
                $thisDeployment,
                'Cleaned deployment ' . $deployment->number,
                [
                    "Deployment Folder - " . $deploymentDir
                ]
            );
        }
    }
}
