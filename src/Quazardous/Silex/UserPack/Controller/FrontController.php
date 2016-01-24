<?php

namespace Quazardous\Silex\UserPack\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller with actions for handling form-based authentication and user management.
 *
 * @package SimpleUser
 */
class FrontController
{
    
    protected $varsInjector;
    
    public function __construct(callable $varsInjector) {
        $this->varsInjector = $varsInjector;
    }
    
    public function login(Application $app, Request $request)
    {
        $vars = [
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ];
        $vars['_check_route'] = $request->get('_check_route'); 
        $vars = call_user_func($this->varsInjector, 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($vars['uns'] . '/front/login.html.twig', $vars);
    }
}
