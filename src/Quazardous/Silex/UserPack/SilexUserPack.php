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
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Quazardous\Silex\UserPack\Exception\UserRegistrationException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Quazardous\Silex\UserPack\Controller\DebugController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Quazardous\Silex\UserPack\Exception\TokenException;

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
        $dns = $this->_ns() . '.';
        $self = $this;
        
        // Add the ResolveTargetEntityListener
        $app->extend('db.event_manager', function ($evs) use ($app, $dns) {
            $app[$dns . 'init_options']();
            $rtel = new \Doctrine\ORM\Tools\ResolveTargetEntityListener;
            // Adds a target-entity class
            $rtel->addResolveTargetEntity('Quazardous\Silex\UserPack\Entity\UserInterface', $app[$dns . 'user_entity_class'], []);
            $evs->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $rtel);
            return $evs;
        });
        
        // allow injection of variables into twig templates
        $app[$dns . 'twig_vars_injector'] = $app->protect(function ($controller, $request, $vars) use ($app, $self, $dns) {
            
            $vars['uns'] = '@' . $self->getName();
            $vars['duns'] = $dns;
            
            return $vars;
        });
        
        $app[$dns . 'password_encoder'] = $app->protect(function ($password, $salt = '') use ($app) {
            $encoder = $app['security.encoder.digest'];
            return $encoder->encodePassword($password, $salt);
        });
        
        $app[$dns . 'controller.front'] = function ($app) use ($self, $dns) {
            return new FrontController([
                'ns' => '@' . $self->getName(),
                'dns' => $dns,
            ]);
        };
        
        if ($app['debug']) {
            $app[$dns . 'controller.debug'] = function ($app) use ($self, $dns) {
                return new DebugController([
                    'ns' => '@' . $self->getName(),
                    'dns' => $dns,
                ]);
            };
        }
        
        $app[$dns . 'default.options'] = [
            $dns . 'firewalls' => [], // firewalls to manage
            $dns . 'user_entity_class' => 'Quazardous\Silex\UserPack\Entity\User',
            $dns . 'token_entity_class' => 'Quazardous\Silex\UserPack\Entity\Token',
            $dns . 'unsecure_mount_prefix' => '/',
            // default register_path, this will be mounted on the 'unsecured_mount_prefix'
            $dns . 'register_path' => '/register',
            $dns . 'use_email_as_username' => false,
            $dns . 'register_roles' => [],
            $dns . 'render_password_value' => true,
            $dns . 'email_verification' => true,
            $dns . 'email_verification_expiration' => 'PT24H',
            $dns . 'recover_password_expiration' => 'PT1H',
            // user will not be enabled until email verification
            $dns . 'require_email_verification' => true,
            $dns . 'auto_connect' => false,
            $dns . 'register_complete_path' => '/register_complete',
            $dns . 'register_confirm_path' => '/register_confirm/{token}',
            $dns . 'recover_password_path' => '/recover_password',
            $dns . 'recover_password_confirm_path' => '/recover_password_confirm/{token}',
            $dns . 'mailer_from' => '',
            // default options for firewall setting
            $dns . 'invalidate_session' => true,
            // default login_path, this will be mounted on the 'unsecured_mount_prefix'
            $dns . 'login_path' => '/login',
            // default 'check_path' and 'logout_path' this will be mounted on the 'secured_mount_prefix'
            $dns . 'check_path' => '/login_check',
            $dns . 'logout_path' => '/logout',
            // change the default parameter names to fit with symfony form
            $dns . 'username_parameter' => 'user_login[_username]',
            $dns . 'password_parameter' => 'user_login[_password]',
        ];
        
        $app[$dns . 'init_options'] = $app->protect(function () use ($app, $dns) {
            static $inited = false;
            if (!$inited) {
                foreach ($app[$dns . 'default.options'] as $key => $value) {
                    if (!isset($app[$key])) {
                        $app[$key] = $value;
                    }
                }
                $inited = true;
            }
        });
        
        // a simple user provider using the loader below
        $app[$dns . 'user_provider'] = function ($app) use ($dns) {
            $app[$dns . 'init_options']();
            return new UserProvider($app[$dns . 'user_loader']);
        };
        
        // a simple user loader
        $app[$dns . 'user_loader'] = $app->protect(function ($username, $securityUser = true) use ($app, $dns) {
            if (empty($app['orm.em'])) {
                throw new \RuntimeException("Doctrine ORM not found");
            }
            $app[$dns . 'init_options']();
            if (isset($app['logger'])) {
                $app['logger']->info("Loading $username...");
            }
            /** @var \Quazardous\Silex\UserPack\Entity\UserInterface $dbUser */
            $dbUser = $app['orm.em']->getRepository($app[$dns . 'user_entity_class'])->findOneBy(['username' => $username]);
            if (empty($dbUser)) {
                if (isset($app['logger'])) {
                    $app['logger']->notice("$username not found");
                }
                return null;
            }
            if (!$securityUser) return $dbUser;
            return new User($dbUser->getUsername(), $dbUser->getPassword(), (array)$dbUser->getRoles(), $dbUser->getEnabled(), true, true, true);
        });
        
        $app[$dns . 'token_converter'] = $app->protect(function ($token) use ($app, $dns) {
            if (empty($app['orm.em'])) {
                throw new \RuntimeException("Doctrine ORM not found");
            }
            $app[$dns . 'init_options']();

            $dbToken = $app['orm.em']->getRepository($app[$dns . 'token_entity_class'])->findOneBy(['token' => $token]);
            if (!$dbToken) {
                throw new NotFoundHttpException("Token not found");
            }
            
            return $app['orm.em']->getRepository($app[$dns . 'token_entity_class'])->findOneBy(['token' => $token]);
        });
        
        $app[$dns . 'token_consumer'] = $app->protect(function ($actuator, $token, $data = []) use ($app, $dns) {
            if (empty($app['orm.em'])) {
                throw new \RuntimeException("Doctrine ORM not found");
            }
            $app[$dns . 'init_options']();
            
            $actuators = [
                'email_verification' => ['register_confirm'],
                'require_email_verification' => ['register_confirm'],
                'recover_password' => ['recover_password_confirm'],
            ];
            /** @var \Quazardous\Silex\UserPack\Entity\Token $token */

            if (empty($actuators[$token->getType()]) || !in_array($actuator, $actuators[$token->getType()])) {
                throw new TokenException("Token too old", TokenException::BAD_USE);
            }
            
            $now = new \DateTime();
            if ($token->getExpiredAt() < $now) {
                throw new TokenException("Token too old", TokenException::TOO_OLD);
            }

            if ($token->getConsumed()) {
                throw new TokenException("Token already used", TokenException::ALREADY_USED);
            }
            
            switch ($token->getType()) {
                case 'email_verification':
                    $token->getUser()->setEmailVerified(true);
                    $token->setConsumed(true);
                    $token->setConsumedAt(new \DateTime());
                    $app['orm.em']->flush();
                    return true;
                case 'require_email_verification':
                    $token->getUser()->setEmailVerified(true);
                    $token->getUser()->setEnabled(true);
                    $token->setConsumed(true);
                    $token->setConsumedAt(new \DateTime());
                    $app['orm.em']->flush();
                    return true;
                case 'recover_password';
                    if ($data) {
                        // submit
                        $token->setConsumed(true);
                        $token->setConsumedAt(new \DateTime());
                        $app['orm.em']->flush();
                    }
                    return true;
            }
            
        });
        
        // build the user_register form
        $app[$dns . 'register_form_builder'] = $app->protect(function ($request) use ($app, $dns) {
            $data = $request->getSession()->get($dns . 'register_form', []);
            $name = $request->get('_firewall');
            
            $builder = $app->namedForm('user_register', $data);
            
            if (!$app[$dns . 'firewalls'][$name]['use_email_as_username']) {
                $builder->add('username', TextType::class, ['label' => $dns . 'register.labels.username']);
            }
            
            /** @var \Symfony\Component\Form\FormBuilder $builder */ 
            $builder->add('email', EmailType::class, ['label' => $dns . 'register.labels.email'])
                ->add('password', PasswordType::class, [
                    'always_empty' => !$app[$dns . 'firewalls'][$name]['render_password_value'],
                    'label' => $dns . 'register.labels.password',
                    'attr' => ['autocomplete' => 'off']])
                ->add('confirm_password', PasswordType::class, [
                    'always_empty' => !$app[$dns . 'firewalls'][$name]['render_password_value'], 
                    'label' => $dns . 'register.labels.confirm_password',
                    'attr' => ['autocomplete' => 'off']])
                ->add('submit', SubmitType::class, ['label' => $dns . 'register.labels.submit']);
            
            if ($request->get('_login_route')) {
                $builder->add('cancel', SubmitType::class, ['label' => $dns . 'register.labels.cancel']);
            }    
                
            return $builder;
        });
        
        // build the validators constraints for the registration user data
        $app[$dns . 'register_constraints_builder'] = $app->protect(function ($request, $data) use ($app, $dns) {
            $name = $request->get('_firewall');
            $constraints = [];
            $constraints['email'] = new Constraints\Email();
            if (!$app[$dns . 'firewalls'][$name]['use_email_as_username']) {
                $constraints['username'] = [
                        new Constraints\Length([
                            'min' => 8,
                            'minMessage' => $dns . 'register.validators.username.length.min',
                            'max' => 16,
                            'maxMessage' => $dns . 'register.validators.username.length.max',     
                        ]),
                        new Constraints\Regex([
                            'pattern' => '/^[a-z][a-z0-9]*$/i',
                            'message' => $dns . 'register.validators.username.regex.username',
                        ]),
                    ];
            }
            $constraints['password'] = new Constraints\Length([
                'min' => 8,
                'minMessage' => $dns . 'register.validators.password.length.min',
                'max' => 16,
                'maxMessage' => $dns . 'register.validators.password.length.max',     
            ]);
            $constraints['confirm_password'] = new Constraints\EqualTo([
                'value' => $data['password'],
                'message' => $dns . 'register.validators.confirm_password.equal_to.password',
            ]);
   
            return $constraints;
        });
        
        $app[$dns . 'password_recoverer'] = $app->protect(function ($request, $email) use ($app, $dns) {
            $app[$dns . 'init_options']();
            $name = $request->get('_firewall');
            /** @var \Quazardous\Silex\UserPack\Entity\UserInterface $dbUser */
            $dbUser = $app['orm.em']->getRepository($app[$dns . 'user_entity_class'])->findOneBy(['email' => $email]);
            if ($dbUser) {
                $dbToken = $app[$dns . 'secure_token_factory']($dbUser, 'recover_password');
                $app['orm.em']->flush();
                
                $data = [
                    'user' => $dbUser,
                    'token' => $dbToken->getToken(),
                    'login_route' => $request->get('_login_route'),
                    'register_route' => $request->get('_register_route'),
                    'recover_password_confirm_route' => $request->get('_recover_password_confirm_route'),
                    'from' => $app[$dns . 'firewalls'][$name]['mailer_from'],
                ];
                $app[$dns . 'mailer']('recover_password', $data);
            }
        });
        
        /**
         * Try to register the given user data.
         * @throws \Quazardous\Silex\UserPack\Exception\UserRegistrationException
         */
        $app[$dns . 'user_registrator'] = $app->protect(function ($request, $data) use ($app, $dns) {
            $app[$dns . 'init_options']();
            $name = $request->get('_firewall');
            
            $request->getSession()->set($dns . 'register_form', $data);

            $constraints = $app[$dns . 'register_constraints_builder']($request, $data);
            $constraints = new Constraints\Collection($constraints);
            $violations = $app['validator']->validate($data, $constraints);
            if (count($violations)) {
                $e = new UserRegistrationException($app['translator']->trans($dns . 'register.errors.validation', [], 'errors'));
                $fieldErrors = [];
                foreach ($violations as $violation) {
                    /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
                    $field = $violation->getPropertyPath();
                    $field = trim($field, '[]');
                    if (!isset($fieldErrors[$field])){
                        $fieldErrors[$field] = [];
                    }
                    $fieldErrors[$field][] = $violation->getMessage();
                }
                $e->setFieldErrors($fieldErrors);
                throw $e;
            }
            if (empty($app['orm.em'])) {
                throw new \RuntimeException("Doctrine ORM not found");
            }
            /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
            $metadata = $app['validator.mapping.class_metadata_factory']->getMetadataFor($app[$dns . 'user_entity_class']);
            $metadata->addConstraint(new UniqueEntity([
                'fields'  => 'email',
                'message' => $dns . 'register.validators.user.unique_entity.email',
            ]));
            $metadata->addConstraint(new UniqueEntity([
                'fields'  => 'username',
                'message' => $dns . 'register.validators.user.unique_entity.username',
            ]));
            $c = $app[$dns . 'user_entity_class'];
            /** @var \Quazardous\Silex\UserPack\Entity\UserInterface $dbUser */
            $dbUser = new $c;
            $dbUser->setEmail($data['email']);
            $dbUser->setUsername($app[$dns . 'firewalls'][$name]['use_email_as_username'] ? $data['email'] : $data['username']);
            $dbUser->setRoles((array)$app[$dns . 'firewalls'][$name]['register_roles']);
            $dbUser->setEmailVerified(false);
            $dbUser->setEnabled(!$app[$dns . 'firewalls'][$name]['require_email_verification']);
            $dbUser->setPassword($app[$dns . 'password_encoder']($data['password']));
            
            $violations = $app['validator']->validate($dbUser);
            if (count($violations)) {
                $e = new UserRegistrationException($app['translator']->trans($dns . 'register.errors.validation', [], 'errors'));
                $fieldErrors = [];
                foreach ($violations as $violation) {
                    /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
                    $field = $violation->getPropertyPath();
                    $field = trim($field, '[]');
                    if (!isset($fieldErrors[$field])){
                        $fieldErrors[$field] = [];
                    }
                    $fieldErrors[$field][] = $violation->getMessage();
                }
                $e->setFieldErrors($fieldErrors);
                throw $e;
            }

            $app['orm.em']->persist($dbUser);
            
            if ($app[$dns . 'firewalls'][$name]['email_verification']) {
                $dbToken = $app[$dns . 'secure_token_factory']($dbUser, $app[$dns . 'firewalls'][$name]['require_email_verification'] ? 'require_email_verification' : 'email_verification');
            }
            
            $app['orm.em']->flush();
            
            if ($app[$dns . 'firewalls'][$name]['email_verification']) {
                $data = [
                    'user' => $dbUser,
                    'token' => $dbToken->getToken(),
                    'require_email_verification' => $app[$dns . 'firewalls'][$name]['require_email_verification'],
                    'login_route' => $request->get('_login_route'),
                    'register_route' => $request->get('_register_route'),
                    'register_confirm_route' => $request->get('_register_confirm_route'),
                    'from' => $app[$dns . 'firewalls'][$name]['mailer_from'],
                ];
                $app[$dns . 'mailer']('email_verification', $data);
            }
            
            return $dbUser->getUsername();
        });
        
        
        $app[$dns . 'secure_token_factory'] = $app->protect(function ($user, $type) use ($app, $dns) {
            $metadata = $app['validator.mapping.class_metadata_factory']->getMetadataFor($app[$dns . 'token_entity_class']);
            $metadata->addConstraint(new UniqueEntity('token'));
            $c = $app[$dns . 'token_entity_class'];
            /** @var \Quazardous\Silex\UserPack\Entity\Token $dbToken */
            $dbToken = new $c($user);
            $dbToken->setType($type);
            $date = new \DateTime();
            $date->add(new \DateInterval($app[$dns . $type .'_expiration']));
            $dbToken->setExpiredAt($date);
            
            for(;;)
            {
                $dbToken->setToken($app[$dns . 'secure_token_generator']());
                $violations = $app['validator']->validate($dbToken);
                if (count($violations) == 0) break;
            }
            
            $app['orm.em']->persist($dbToken);
            return $dbToken;
        });
        
        // a secure token generator
        $app[$dns . 'secure_token_generator'] = $app->protect(function($lenght = 32) {
            return substr(bin2hex(openssl_random_pseudo_bytes((int) ceil($lenght / 2.0))), 0, $lenght);
        });
        
        $app[$dns . 'path_sanitizer'] = $app->protect(function($path) {
            $path = preg_replace('#/+#','/', $path);
            return rtrim($path, '/');
        });
        
        $app[$dns . 'route_name_builder'] = $app->protect(function($path, $prefixWithDns = true) use ($self, $dns) {
            $route = strtr(ltrim($path, '/'), '/{}', '___');
            if ($prefixWithDns) {
                $route = $dns . $route;
            }
            return $route;
        });
        
        $app[$dns . 'mailer'] = $app->protect(function($email, $vars) use ($app, $self, $dns) {
            $ns = '@' . $self->getName();
            if (!isset($app['mailer'])) {
                throw new \RuntimeException("You must provide a mailer");
            }
            $vars['uns'] = $ns;
            $vars['duns'] = $dns;
            switch ($email) {
                case 'email_verification':
                    $vars += [
                        'subject' => $app['translator']->trans($vars['require_email_verification'] ? $dns . 'require_email_verification.subject' : $dns . 'email_verification.subject'),
                        'to' => $vars['user']->getEmail(),
                    ];
                    $tpl = $vars['require_email_verification'] ? 'require_email_verification' : 'email_verification';
                    break;
                case 'recover_password':
                    $vars += [
                        'subject' => $app['translator']->trans($dns . 'email_recover_password.subject'),
                        'to' => $vars['user']->getEmail(),
                    ];
                    $tpl = $email;
                    break;
                default:
                    throw new \RuntimeException("$email : unknown mail type");
            }
            
            $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'email:' . $email, null, $vars);
            
            $message = \Swift_Message::newInstance()
                ->setSubject($vars['subject'])
                ->setFrom(array($vars['from']))
                ->setTo(array($vars['to']))
                ->setBody($app['twig']->render("$ns/email/$tpl.txt.twig", $vars), 'text/plain')
                ->addPart($app['twig']->render("$ns/email/$tpl.html.twig", $vars), 'text/html');
            
            $app['mailer']->send($message);
        });
        
        // inject user pack routes and options into managed firewalls
        if (!isset($app['security.firewalls'])) {
            throw new \RuntimeException("You must provide the security layer before user pack");
        }
        $firewalls = $app['security.firewalls'];
        $app['security.firewalls'] = function ($app) use ($firewalls, $dns) {
            $app[$dns . 'init_options']();
            $sanitizer = $app[$dns . 'path_sanitizer'];
            $injected_paths = [];
            $all_paths = [];
            $user_firewalls = [];
            foreach ($app[$dns . 'firewalls'] as $name => $options) {
                if (empty($options)) {
                    $options = [];
                }
                
                $options += [
                    'unsecure_mount_prefix' => $app[$dns . 'unsecure_mount_prefix'],
                    'login_path' => $app[$dns . 'login_path'],
                    'check_path' => $app[$dns . 'check_path'],
                    'logout_path' => $app[$dns . 'logout_path'],
                    'invalidate_session' => $app[$dns . 'invalidate_session'],
                    'username_parameter' => $app[$dns . 'username_parameter'],
                    'password_parameter' => $app[$dns . 'password_parameter'],
                    'register_path' => $app[$dns . 'register_path'],
                    'use_email_as_username' => $app[$dns . 'use_email_as_username'],
                    'render_password_value' => $app[$dns . 'render_password_value'],
                    'email_verification' => $app[$dns . 'email_verification'],
                    'require_email_verification' => $app[$dns . 'require_email_verification'],
                    'auto_connect' => $app[$dns . 'auto_connect'],
                    'register_roles' => $app[$dns . 'register_roles'],
                    'register_complete_path' => $app[$dns . 'register_complete_path'],
                    'mailer_from' => $app[$dns . 'mailer_from'],
                    'register_confirm_path' => $app[$dns . 'register_confirm_path'],
                    'recover_password_path' => $app[$dns . 'recover_password_path'],
                    'recover_password_confirm_path' => $app[$dns . 'recover_password_confirm_path'],
                ];
                
                if ($options['require_email_verification']) {
                    $options['email_verification'] = $options['require_email_verification'];
                }
                
                $options['register_roles'] = (array)$options['register_roles'];
                
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
                
                if (array_key_exists($name, $firewalls)) {
                    // inject login stuff
                    if (array_key_exists('form', $firewalls[$name])) {
                        if (empty($firewalls[$name]['form'])) {
                            $firewalls[$name]['form'] = [];
                        }
                        if (empty($firewalls[$name]['form']['login_path'])) {
                            $path = '/' . $options['unsecure_mount_prefix'] . '/' . $options['login_path'];
                            $firewalls[$name]['form']['login_path'] = $sanitizer($path);
                            // keep track of the path we have injected
                            $injected_paths[$name]['login_path'] = $firewalls[$name]['form']['login_path'];
                        }
                        $all_paths[$name]['login_path'] = $firewalls[$name]['form']['login_path'];
                        if (empty($firewalls[$name]['form']['check_path'])) {
                            $path = '/' . $options['secured_mount_prefix'] . '/' . $options['check_path']; 
                            $firewalls[$name]['form']['check_path'] = $sanitizer($path);
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
                    // inject logout stuff
                    if (array_key_exists('logout', $firewalls[$name])) {
                        if (empty($firewalls[$name]['logout'])) {
                            $firewalls[$name]['logout'] = [];
                        }
                        if (empty($firewalls[$name]['form']['logout_path'])) {
                            $path = '/' . $options['secured_mount_prefix'] . '/' . $options['logout_path']; 
                            $firewalls[$name]['logout']['logout_path'] = $sanitizer($path);
                            // keep track of the path we have injected
                            $injected_paths[$name]['logout_path'] = $firewalls[$name]['logout']['logout_path'];
                        }
                        $all_paths[$name]['logout_path'] = $firewalls[$name]['logout']['logout_path'];
                        if (empty($firewalls[$name]['form']['invalidate_session'])) {
                            $firewalls[$name]['logout']['invalidate_session'] = $options['invalidate_session']; 
                        }
                    }
                    // inject user provider
                    if (empty($firewalls[$name]['users'])) {
                        $firewalls[$name]['users'] = function () use ($app, $dns) {
                            return $app[$dns . 'user_provider'];
                        };
                    }
                    // handle register stuff
                    foreach (['register_path', 'register_complete_path', 'register_confirm_path', 'recover_password_path', 'recover_password_confirm_path'] as $p) {
                        if (!empty($options[$p])) {
                            $path = $sanitizer('/' . $options['unsecure_mount_prefix'] . '/' . $options[$p]);
                            $injected_paths[$name][$p] = $path;
                            $all_paths[$name][$p] = $path;
                        }
                    }
                }
                $user_firewalls[$name] = $options;
            }
            $app[$dns . 'firewalls'] = $user_firewalls;
            $app[$dns . 'injected_paths'] = $injected_paths;
            $app[$dns . 'all_paths'] = $all_paths;
            return $firewalls;
        };
    }

    public function connect(Application $app)
    {
        $app['security.firewalls'];
        $dns = $this->_ns() . '.';
        $builder = $app[$dns . 'route_name_builder'];
        
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        
        // add the login routes and controllers
        $added_routes = [];
        foreach ($app[$dns . 'injected_paths'] as $name => $paths) {
            $c = [];
            $r = [];
            if (isset($paths['login_path'])) {
                if (empty($app[$dns . 'all_paths'][$name]['check_path'])) {
                    throw new \LogicException("No check_path for login_path " . $paths['login_path']);
                }
                
                $r['login'] = $builder($paths['login_path']);
                if (empty($added_routes[$r['login']])) {
                    // add a route only once
                    $checkLoginRoute = $builder($app[$dns . 'all_paths'][$name]['check_path'], false);
                    $c['login'] = $controllers->get($paths['login_path'], $this->_ns('controller.front:loginAction'))
                        ->value('_check_route', $checkLoginRoute)
                        ->bind($r['login']);
                    $added_routes[$r['login']] = true;
                }
            }
            if (!$r['login'] && !empty($app['security.firewalls'][$name]['form']['login_path'])) {
                $r['login'] = $builder($app['security.firewalls'][$name]['form']['login_path'], false);
            }

            if (isset($paths['register_confirm_path'])) {
                $r['register_confirm'] = $builder($paths['register_confirm_path']);
                if (empty($added_routes[$r['register_confirm']])) {
                    // add a route only once
                    $c['register_confirm'] = $controllers->get($paths['register_confirm_path'], $this->_ns('controller.front:registerConfirmAction'))
                        ->convert('token', $app[$dns . 'token_converter'])
                        ->bind($r['register_confirm']);
                    $added_routes[$r['register_confirm']] = true;
                }
            }
            if (isset($paths['register_path'])) {
                if (empty($paths['register_complete_path'])) {
                    throw new \LogicException("No register_complete_path for register_path " . $paths['register_path']);
                }
                $registerCompleteRoute = $builder($paths['register_complete_path']);
                if (empty($added_routes[$registerCompleteRoute])) {
                    // add a route only once
                    $c['register_complete'] = $controllers->get($paths['register_complete_path'], $this->_ns('controller.front:registerCompleteAction'))
                        ->bind($registerCompleteRoute);
                    $added_routes[$registerCompleteRoute] = true;
                }

                $r['register'] = $builder($paths['register_path']);
                if (empty($added_routes[$r['register']])) {
                    // add a route only once
                    $c['register'] = $controllers->match($paths['register_path'], $this->_ns('controller.front:registerAction'))
                        ->bind($r['register'])
                        ->value('_register_complete_route', $registerCompleteRoute);
                    
                    $added_routes[$r['register']] = true;
                }
            }
            if (isset($paths['recover_password_path'])) {
                $r['recover_password'] = $builder($paths['recover_password_path']);
                if (empty($added_routes[$r['recover_password']])) {
                    // add a route only once
                    $c['recover_password'] = $controllers->match($paths['recover_password_path'], $this->_ns('controller.front:recoverPasswordAction'))
                        ->bind($r['recover_password']);
                    $added_routes[$r['recover_password']] = true;
                }
            }
            if (isset($paths['recover_password_confirm_path'])) {
                $r['recover_password_confirm'] = $builder($paths['recover_password_confirm_path']);
                if (empty($added_routes[$r['recover_password_confirm']])) {
                    // add a route only once
                    $c['recover_password_confirm'] = $controllers->match($paths['recover_password_confirm_path'], $this->_ns('controller.front:recoverPasswordConfirmAction'))
                        ->convert('token', $app[$dns . 'token_converter'])
                        ->bind($r['recover_password_confirm']);
                    $added_routes[$r['recover_password_confirm']] = true;
                }
            }
            
            // inject public routes in all controllers
            foreach ($c as $cv) {
                foreach ($r as $rk => $rv) {
                    $cv->value("_{$rk}_route", $rv);
                }
                $cv->value("_firewall", $name);
            }
        }
        
        if ($app['debug']) {
            $email = $controllers->get('/_user/debug/email/{email}.{ext}', $this->_ns('controller.debug:displayEmailAction'));
            foreach ($r as $rk => $rv) {
                $email->value("_{$rk}_route", $rv);
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
