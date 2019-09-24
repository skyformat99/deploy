<?php

namespace App\Controller;

use App\Facades\Router;
use App\Facades\Security;
use App\Facades\Session;
use App\Facades\View;
use App\Model\User;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Controller for user related actions
 *
 * @author Ronan Chilvers <ronan@d3r.com>
 */
class UserController
{
    /**
     * Login action for users
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function login(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $user = new User();
        if ($request->isMethod('POST')) {
            try {
                $data = $request->getParsedBody();
                if (!isset($data['email'], $data['password'])) {
                    throw new RuntimeException('Email / password are required');
                }
                $user = Security::login(
                    $data['email'],
                    $data['password']
                );
                if ($user instanceof User) {
                    return $response->withRedirect(
                        Router::pathFor('project.index')
                    );
                }
                throw new RuntimeException('Unable to log you in');
            } catch (Exception $ex) {
                Session::flash(
                    [
                        'heading' => $ex->getMessage(),
                    ],
                    'error'
                );
                return $response->withRedirect(
                    Router::pathFor('user.login')
                );
            }
        }

        return View::render(
            $response,
            'user/login.html.twig',
            [
                'user' => $user,
            ]
        );
    }

    /**
     * Logout action
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function logout(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        Security::logout();

        return $response->withRedirect(
            Router::pathFor('user.login')
        );
    }

    /**
     * Save the favourite deployments for a user
     *
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    public function favourite(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $args
    ) {
        $error    = false;
        $selected = false;
        $user     = Security::user();
        $project  = $args['project'];
        if (0 < $project) {
            $favourites = $user->preference('favourites', []);
            switch (isset($favourites[$project])) {

                // Not in favourites
                case false:
                    $favourites[$project] = $project;
                    $selected         = true;
                    break;

                // In favourites
                default:
                    unset($favourites[$project]);
                    $selected = false;
                    break;

            }
            $favourites = array_filter($favourites);
            if (!$user->setPreference('favourites', $favourites)) {
                $error = true;
            }
        }
        $json = [
            'result' => ($error) ? 'error' : 'ok',
            'data' => [
                'project'    => $project,
                'selected'   => $selected,
                'favourites' => $user->preference('favourites', []),
            ]
        ];

        return $response->withJson($json);
    }
}
