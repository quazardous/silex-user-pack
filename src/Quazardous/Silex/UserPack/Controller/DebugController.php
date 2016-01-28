<?php

namespace Quazardous\Silex\UserPack\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Debug stuff.
 *
 * @package SimpleUser
 */
class DebugController
{
    
    protected $options = [
        'ns' => null,
        'dns' => null,
    ];
    
    public function __construct(array $options = []) {
        $this->options = array_merge($this->options, $options);
    }
    
    public function displayEmailAction(Application $app, Request $request, $email, $ext)
    {
        $dns = $this->options['dns'];
        $vars = [
            'login_route' => $request->get('_login_route'),
            'register_confirm_route' => $request->get('_register_confirm_route'),
            'register_route' => $request->get('_register_route'),
            'recover_password_confirm_route' => $request->get('_recover_password_confirm_route'),
            'from' => 'no-reply@sup.net',
            'to' => 'johndoe@whatever.com',
        ];
        
        switch ($email) {
            case 'require_email_verification':
                $vars['subject'] = $app['translator']->trans($dns . 'require_email_verification.subject');
                $vars['token'] = 'xxxTOKENxxx';
                break;
            case 'email_verification':
                $vars['subject'] = $app['translator']->trans($dns . 'email_verification.subject');
                $vars['token'] = 'xxxTOKENxxx';
                break;
            case 'recover_password':
                $vars['subject'] = $app['translator']->trans($dns . 'email_recover_password.subject');
                $vars['token'] = 'xxxTOKENxxx';
                break;
            default:
                $app->abort(404, "$email not found");
        }
        
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'email:' . $email, $request, $vars);
        return $app->renderView($this->options['ns'] . "/email/$email.$ext.twig", $vars);
    }

}
