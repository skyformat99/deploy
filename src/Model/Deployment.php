<?php

namespace App\Model;

use App\Model\Event;
use App\Model\Finder\DeploymentFinder;
use App\Model\Project;
use Carbon\Carbon;
use Respect\Validation\Validator;
use Ronanchilvers\Orm\Model;
use Ronanchilvers\Orm\Traits\HasValidationTrait;

/**
 * Model representing a project deployment
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class Deployment extends Model
{
    use HasValidationTrait;

    static protected $finder       = DeploymentFinder::class;
    static protected $columnPrefix = 'deployment';

    protected $data = [
        'deployment_status' => 'pending'
    ];

    /**
     * Boot the model
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function boot()
    {
        $this->addType('datetime', 'started');
        $this->addType('datetime', 'finished');
    }

    /**
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function setupValidation()
    {
        $this->registerRules([
            'project' => Validator::notEmpty()->intVal()->min(1),
            'hash'    => Validator::stringType(),
            'status'  => Validator::notEmpty()->in(['pending', 'deploying', 'deployed', 'failed']),
        ]);
    }

    /**
     * Relationship with project
     *
     * @return App\Model\Project
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function relateProject()
    {
        return $this->belongsTo(
            Project::class

        );
    }

    /**
     * Relate events to this deployment
     *
     * @return array
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function relateEvents()
    {
        return $this->hasMany(
            Event::class
        );
    }

    /**
     * Start the deployment
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function start()
    {
        $this->status  = 'deploying';
        $this->started = Carbon::now();

        return $this->save();
    }

    /**
     * Finish the deployment
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function finish()
    {
        $this->status   = 'deployed';
        $this->finished = Carbon::now();

        return $this->save();
    }

    /**
     * Mark the deployment as failed
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function fail()
    {
        $this->status = 'failed';
        $this->failed = Carbon::now();

        return $this->save();
    }

    /**
     * Is the deployment deployed?
     *
     * @return boolean
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function isDeployed()
    {
        return 'deployed' == $this->status;
    }

    /**
     * Is the deployment deploying?
     *
     * @return boolean
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function isDeploying()
    {
        return 'deploying' == $this->status;
    }

    /**
     * Is the deployment pending?
     *
     * @return boolean
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function isPending()
    {
        return 'pending' == $this->status;
    }

    /**
     * Is the deployment failed?
     *
     * @return boolean
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function isFailed()
    {
        return 'failed' == $this->status;
    }

    /**
     * Get the duration in seconds for this deployment
     *
     * @return int
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function getDuration()
    {
        if ('deployed' != $this->status) {
            return null;
        }
        return $this->finished->diffInSeconds($this->started);
    }
}
