<?php

namespace App\Controller;

use App\Facades\Log;
use App\Facades\Provider;
use App\Facades\Router;
use App\Facades\Session;
use App\Facades\View;
use App\Model\Deployment;
use App\Model\Event;
use App\Model\Project;
use App\Queue\DeployJob;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ronanchilvers\Foundation\Facade\Queue;
use Ronanchilvers\Orm\Orm;

/**
 * Controller for the index
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class ProjectController
{
    /**
     * Index action
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function index(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $projects = Orm::finder(Project::class)->all();

        return View::render(
            $response,
            'project/index.html.twig',
            [
                'projects' => $projects
            ]
        );
    }

    /**
     * View a project dashboard
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function view(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $args
    ) {
        if (!$project = $this->projectFromArgs($args)) {
            return $response->withRedirect(
                Router::pathFor('project.index')
            );
        }

        $finder   = Orm::finder(Deployment::class);
        $deployments = $finder->forProject($project);

        $selected_number = $request->getQueryParam(
            'deployment',
            (0 < count($deployments)) ? $deployments[0]->number : false
        );
        $selectedDeployment = (0 < count($deployments)) ? $deployments[0] : false ;
        foreach ($deployments as $deployment) {
            if ($deployment->number == $selected_number) {
                $selectedDeployment = $deployment;
                break;
            }
        }
        $events = [];
        if ($selectedDeployment) {
            $events = $selectedDeployment->events;
        }

        return View::render(
            $response,
            'project/view.html.twig',
            [
                'project'             => $project,
                'deployments'         => $deployments,
                'selected_deployment' => $selectedDeployment,
                'events'              => $events,
            ]
        );
    }

    /**
     * Add a project
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function add(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $project = new Project();
        if ($request->isMethod('POST')) {
            $data = $request->getParsedBody()['project'];
            $project->fromArray($data);
            if ($project->saveWithValidation()) {
                Session::flash([
                    'heading' => 'Project added'
                ], 'success');
                return $response->withRedirect(
                    Router::pathFor('project.index')
                );
            }
        }

        return View::render(
            $response,
            'project/add.html.twig',
            [
                'project' => $project,
            ]
        );
    }

    /**
     * Edit action
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function edit(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $args
    ) {
        if (!$project = $this->projectFromArgs($args)) {
            return $response->withRedirect(
                Router::pathFor('project.index')
            );
        }
        if ($request->isMethod('POST')) {
            $data = $request->getParsedBody()['project'];
            $project->fromArray($data);
            if ($project->saveWithValidation()) {
                Session::flash([
                    'heading' => 'Project saved'
                ]);
                return $response->withRedirect(
                    Router::pathFor('project.edit', [
                        'key' => $project->key
                    ])
                );
            }
        }

        return View::render(
            $response,
            'project/edit.html.twig',
            [
                'project' => $project
            ]
        );
    }

    /**
     * Trigger a deploy for a project
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function deploy(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $args
    ) {
        try {
            if (!$project = $this->projectFromArgs($args)) {
                return $response->withRedirect(
                    Router::pathFor('project.index')
                );
            }
            $provider = Provider::forProject(
                $project
            );
            $deployment = Orm::finder(Deployment::class)->nextForProject(
                $project
            );
            $finder = Orm::finder(Event::class);
            $head = $provider->getHeadInfo(
                $project,
                function ($type, $header, $detail = '') use ($finder, $deployment) {
                    $finder->event(
                        $type,
                        $deployment,
                        $header,
                        $detail
                    );
                }
            );
            Log::debug('Updating deployment commit information', $head);
            $deployment->sha     = $head['sha'];
            $deployment->author  = $head['author'];
            $deployment->message = $head['message'];
            if (!$deployment->save()) {
                // @todo Show error to user
                return $response->withRedirect(
                    Router::pathFor('project.view', [
                        'key' => $project->key
                    ])
                );
            }
            Queue::dispatch(
                new DeployJob($deployment)
            );
            Session::flash([
                'heading' => 'Deploy queued successfully'
            ]);
        } catch (Exception $ex) {
            Session::flash(
                [
                    'subtitle' => 'Failed to initialise new deployment',
                    'content'  => $ex->getMessage(),
                ],
                'danger'
            );
            Log::error('Failed to initialise new deployment', [
                'exception' => $ex,
            ]);
        }

        // @todo Show confirmation to user
        return $response->withRedirect(
            Router::pathFor('project.view', [
                'key' => $project->key
            ])
        );
    }

    /**
     * Get a project from an args array
     *
     * @param array $args
     * @return App\Model\Project|null
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function projectFromArgs($args)
    {
        $project = Orm::finder(Project::class)->forKey($args['key']);
        if ($project instanceof Project) {
            return $project;
        }

        return null;
    }
}
