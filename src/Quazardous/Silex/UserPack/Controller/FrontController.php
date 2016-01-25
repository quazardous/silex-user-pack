<?php

namespace Quazardous\Silex\UserPack\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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
            '_last_username' => $app['session']->get('_security.last_username'),
        ];
        
        $data = [
            '_username' => $app['session']->get('_security.last_username'),
        ];
        
        $userLoginForm = $app->namedForm('user_login', $data)
            ->setAction($app->path($request->get('_check_route')))
            ->add('_username', TextType::class)
            ->add('_password', PasswordType::class)
            ->add('submit', SubmitType::class)
            ->getForm();
        
        $vars['user_login_form'] = $userLoginForm->createView();
        $vars['_check_route'] = $request->get('_check_route');
        $vars = call_user_func($this->varsInjector, 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($vars['uns'] . '/front/login.html.twig', $vars);
    }
    
}
