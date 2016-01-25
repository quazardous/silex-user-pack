<?php
namespace Quazardous\Silex\UserPack;

use Pimple\Container;
use Silex\Application;
use Quazardous\Silex\Pack\JetPackTrait;
use Quazardous\Silex\UserPack\Controller\FrontController;
use Quazardous\Silex\UserPack\Command\FixtureCommand;
use Quazardous\Silex\UserPack\Command\PasswordCommand;
use Quazardous\Silex\Pack\JetPackInterface;
use Silex\ControllerCollection;
use Quazardous\Silex\UserPack\Security\UserProvider;
use Symfony\Component\Security\Core\User\User;

class SilexUserPack implements JetPackInterface
{
    use JetPackTrait;

    public function getName() {
        // A short name
        return 'User';
    }
    
    public function register(Container $app)
    {
        /** @var \Quazardous\Silex\PackableApplication $app */
        $nsp = $this->_ns() . '.';
        $self = $this;
        
        // allow injection of variables into twig templates
        $app[$nsp . 'twig_vars_injector'] = $app->protect(function ($controller, $request, $vars) use ($app, $self) {
            
            $vars['uns'] = '@' . $self->getName();
            
            return $vars;
        });
        
        $app[$nsp . 'controller.front'] = function ($app) use ($self, $nsp) {
            return new FrontController($app[$nsp . 'twig_vars_injector']);
        };
        
        $app[$nsp . 'default.options'] = [
            $nsp . 'firewalls' => [], // firewalls to manage
            $nsp . 'user_entity_class' => 'Quazardous\Silex\UserPack\Entity\User',
            $nsp . 'unsecure_mount_prefix' => '/',
            // default options for firewall setting
            $nsp . 'invalidate_session' => true,
            // default login_path, this will be mounted on the 'unsecured_mount_prefix'
            $nsp . 'login_path' => '/login',
            // default 'check_path' and 'logout_path' this will be mounted on the 'secured_mount_prefix'
            $nsp . 'check_path' => '/login_check',
            $nsp . 'logout_path' => '/logout',
            $nsp . 'username_parameter' => 'user_login[_username]',
            $nsp . 'password_parameter' => 'user_login[_password]',
            
        ];
        
        $app[$nsp . 'init_options'] = $app->protect(function () use ($app, $nsp) {
            static $inited = false;
            if (!$inited) {
                foreach ($app[$nsp . 'default.options'] as $key => $value) {
                    if (!isset($app[$key])) {
                        $app[$key] = $value;
                    }
                }
                $inited = true;
            }
        });
        
        $app[$nsp . 'user_provider'] = function ($app) use ($nsp) {
            $app[$nsp . 'init_options']();
            return new UserProvider($app[$nsp . 'user_loader']);
        };
        
        $app[$nsp . 'user_loader'] = $app->protect(function ($username) use ($app, $nsp) {
            if (empty($app['orm.em'])) {
                throw new \RuntimeException("Doctrine ORM not found");
            }
            $app[$nsp . 'init_options']();
            if (isset($app['logger'])) {
                $app['logger']->info("Loading $username...");
            }
            /** @var \Quazardous\Silex\UserPack\Entity\User $dbUser */
            $dbUser = $app['orm.em']->getRepository($app[$nsp . 'user_entity_class'])->findOneBy(['username' => $username]);
            if (empty($dbUser)) {
                if (isset($app['logger'])) {
                    $app['logger']->notice("$username not found");
                }
                return null;
            }
            return new User($dbUser->getUsername(), $dbUser->getPassword(), (array)$dbUser->getRoles(), true, true, true, true);
        });
        
        // inject user pack routes and options into managed firewalls
        if (!isset($app['security.firewalls'])) {
            throw new \RuntimeException("You must provide the security layer");
        }
        $firewalls = $app['security.firewalls'];
        $app['security.firewalls'] = function ($app) use ($firewalls, $nsp) {
            $app[$nsp . 'init_options']();
            $injected_paths = [];
            $all_paths = [];
            foreach ($app[$nsp . 'firewalls'] as $name => $options) {
                if (empty($options)) {
                    $options = [];
                }
                if (empty($options['secured_mount_prefix'])) {
                    // try to guess the secured_mount_prefix
                    if (empty($firewalls[$name]['pattern'])) {
                        throw new \RuntimeException('No pattern to guess the prefix');
                    }
                    $matches = null;
                    if (!preg_match("|^\^(/[a-z0-9_/-]*)$|i", $firewalls[$name]['pattern'], $matches)) {
                        throw new \RuntimeException('Cannot guess the prefix from this pattern : ' . $firewalls[$name]['pattern']);
                    }
                    $options['secured_mount_prefix'] = $matches[1];
                }
                $options += [
                    'login_path' => $app[$nsp . 'login_path'],
                    'check_path' => $app[$nsp . 'check_path'],
                    'logout_path' => $app[$nsp . 'logout_path'],
                    'invalidate_session' => $app[$nsp . 'invalidate_session'],
                    'username_parameter' => $app[$nsp . 'username_parameter'],
                    'password_parameter' => $app[$nsp . 'password_parameter'],
                ];
                if (array_key_exists($name, $firewalls)) {
                    if (array_key_exists('form', $firewalls[$name])) {
                        if (empty($firewalls[$name]['form'])) {
                            $firewalls[$name]['form'] = [];
                        }
                        if (empty($firewalls[$name]['form']['login_path'])) {
                            $path = '/' . $app[$nsp . 'unsecure_mount_prefix'] . '/' . $options['login_path'];
                            $path = preg_replace('#/+#','/', $path);
                            $firewalls[$name]['form']['login_path'] = rtrim($path, '/');
                            // keep track of the path we have injected
                            $injected_paths[$name]['login_path'] = $firewalls[$name]['form']['login_path'];
                        }
                        $all_paths[$name]['login_path'] = $firewalls[$name]['form']['login_path'];
                        if (empty($firewalls[$name]['form']['check_path'])) {
                            $path = '/' . $options['secured_mount_prefix'] . '/' . $options['check_path']; 
                            $path = preg_replace('#/+#','/', $path);
                            $firewalls[$name]['form']['check_path'] = rtrim($path, '/');
                            // keep track of the path we have injected
                            $injected_paths[$name]['check_path'] = $firewalls[$name]['form']['check_path'];
                        }
                        $all_paths[$name]['check_path'] = $firewalls[$name]['form']['check_path'];
                        if (empty($firewalls[$name]['form']['username_parameter'])) {
                            $firewalls[$name]['form']['username_parameter'] = $options['username_parameter']; 
                        }
                        if (empty($firewalls[$name]['form']['password_parameter'])) {
                            $firewalls[$name]['form']['password_parameter'] = $options['password_parameter']; 
                        }
                    }
                    if (array_key_exists('logout', $firewalls[$name])) {
                        if (empty($firewalls[$name]['logout'])) {
                            $firewalls[$name]['logout'] = [];
                        }
                        if (empty($firewalls[$name]['form']['logout_path'])) {
                            $path = '/' . $options['secured_mount_prefix'] . '/' . $options['logout_path']; 
                            $path = preg_replace('#/+#','/', $path);
                            $firewalls[$name]['logout']['logout_path'] = rtrim($path, '/');
                            // keep track of the path we have injected
                            $injected_paths[$name]['logout_path'] = $firewalls[$name]['logout']['logout_path'];
                        }
                        $all_paths[$name]['logout_path'] = $firewalls[$name]['logout']['logout_path'];
                        if (empty($firewalls[$name]['form']['invalidate_session'])) {
                            $firewalls[$name]['logout']['invalidate_session'] = $options['invalidate_session']; 
                        }
                    }
                    if (empty($firewalls[$name]['users'])) {
                        $firewalls[$name]['users'] = function () use ($app, $nsp) {
                            return $app[$nsp . 'user_provider'];
                        };
                    }
                }
            }
            $app[$nsp . 'injected_paths'] = $injected_paths;
            $app[$nsp . 'all_paths'] = $all_paths;
            return $firewalls;
        };
    }

    public function connect(Application $app)
    {
        $app['security.firewalls'];
        $nsp = $this->_ns() . '.';
        
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        
        // add the login routes and controllers
        $added_routes = [];
        foreach ($app[$nsp . 'injected_paths'] as $name => $paths) {
            if (isset($paths['login_path'])) {
                if (empty($app[$nsp . 'all_paths'][$name]['check_path'])) {
                    throw new \LogicException("No check_path for login_path " . $paths['login_path']);
                }
                
                $loginRoute = str_replace('/', '_', ltrim($paths['login_path'], '/'));
                if (isset($added_routes[$loginRoute])) {
                    // add a route only once
                    continue;
                }
                $checkLoginRoute = str_replace('/', '_', ltrim($app[$nsp . 'all_paths'][$name]['check_path'], '/'));
                $controllers->get($paths['login_path'], $this->_ns('controller.front:login'))
                    ->value('_check_route', $checkLoginRoute)
                    ->bind($this->_ns($loginRoute));
                $added_routes[$loginRoute] = true;
            }
        }
        
        return $controllers;
    }

    public function getConsoleCommands()
    {
        return [
            new FixtureCommand(),
            new PasswordCommand()
        ];
    }
}
