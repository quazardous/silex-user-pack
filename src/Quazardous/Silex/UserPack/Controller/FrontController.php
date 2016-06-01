<?php

namespace Quazardous\Silex\UserPack\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Quazardous\Silex\UserPack\Exception\UserRegistrationException;
use Symfony\Component\Form\FormError;
use Quazardous\Silex\UserPack\Exception\TokenException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints;

/**
 * Controller with actions for handling form-based authentication and user management.
 *
 * @package SimpleUser
 */
class FrontController
{
    protected $publicRoutes = ['_login_route', '_register_route', '_recover_password_route'];
    
    protected $options = [
        'ns' => null,
        'dns' => null,
    ];
    
    public function __construct(array $options = []) {
        $this->options = array_merge($this->options, $options);
    }
    
    public function loginAction(Application $app, Request $request)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');
        
        $vars = [
            'error'          => $app['security.last_error']($request),
            '_last_username' => $app['session']->get('_security.last_username'),
            'title'          => $app['translator']->trans($dns . 'login.title'),
        ];
        
        $data = [
            '_username' => $app['session']->get('_security.last_username'),
        ];
        
        $userLoginForm = $app->namedForm('user_login', $data)
            ->setAction($app->path($request->get('_check_route')))
            ->add('_username', TextType::class, ['label' => $dns . 'login.labels.username'])
            ->add('_password', PasswordType::class, ['label' => $dns . 'login.labels.password'])
            ->add('submit', SubmitType::class, ['label' => $dns . 'login.labels.submit'])
            ->getForm();
        
        $vars['user_login_form'] = $userLoginForm->createView();
        $vars['_check_route'] = $request->get('_check_route');
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . '/front/login.html.twig', $vars);
    }

    public function registerAction(Application $app, Request $request)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');
        $vars = [
            'title' => $app['translator']->trans($dns . 'register.title'),
        ];
        $registrationException = $request->getSession()->get($dns . 'register_exception', []);
        $registrationException += [
            'field_errors' => [],
            'message' => null,
        ];

        $vars['error'] = $registrationException['message'];
     
        $userRegisterForm = $app[$dns . 'register_form_builder']($request)->getForm();
        
        $userRegisterForm->handleRequest($request);
        
        if ($userRegisterForm->isSubmitted()) {
            if ($userRegisterForm->isValid()) {
                if ($userRegisterForm->get('cancel')->isClicked()) {
                    if ($request->get('_login_route')) {
                        // cancel was clicked, cleanup session
                        $request->getSession()->remove($dns . 'register_form');
                        $request->getSession()->remove($dns . 'register_exception');
                        return $app->redirect($app->path($request->get('_login_route')));
                    }
                } elseif ($userRegisterForm->get('submit')->isClicked()) {
                    $data = $userRegisterForm->getData();
                    try {
                        $username = $app[$dns . 'user_registrator']($request, $data);
                        $request->getSession()->remove($dns . 'register_form');
                        $request->getSession()->remove($dns . 'register_exception');
                        $request->getSession()->set($dns . 'register_complete', $username);
                        if ($app[$dns . 'firewalls'][$firewall]['auto_connect']) {
                            //TODO
                        } else {
                            return $app->redirect($app->path($request->get('_register_complete_route')));
                        }
                    } catch (UserRegistrationException $e) {
                        $registrationException = [
                            'field_errors' => $e->getFieldErrors(),
                            'message' => $e->getMessage(),
                        ];
                        $request->getSession()->set($dns . 'register_exception', $registrationException);
                    }
                }
            }
        }
        
        if ($request->isMethod('POST')) {
            return $app->redirect($request->getUri());
        }
        
        foreach ($registrationException['field_errors'] as $field => $errors) {
            if ($userRegisterForm->has($field)) {
                foreach ($errors as $error) {
                    $userRegisterForm->get($field)->addError(new FormError($error));
                }
            }
        }
        
        $vars['user_register_form'] = $userRegisterForm->createView();
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . '/front/register.html.twig', $vars);
    }
    
    public function registerCompleteAction(Application $app, Request $request)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');
        
        $username = $request->getSession()->get($dns . 'register_complete', false);
        if (!$username) {
            if ($request->get('_login_route')) {
                return $app->redirect($app->path($request->get('_login_route')));
            } else {
                $app->abort(404);
            }
        }
        
        $user = $app[$dns . 'user_loader']($username);
        
        $vars = [
            'title' => $app['translator']->trans($dns . 'register_complete.title'),
            '_user' => $user,
        ];
        $firewall = $request->get('_firewall');
        $request->getSession()->remove($dns . 'register_complete');
        $vars['_require_email_verification'] = $app[$dns . 'firewalls'][$firewall]['require_email_verification'];
        $vars['_email_verification'] = $app[$dns . 'firewalls'][$firewall]['email_verification'];
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . '/front/register_complete.html.twig', $vars);
    }
    
    public function registerConfirmAction(Application $app, Request $request, $token)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');
    
        /** @var \Quazardous\Silex\UserPack\Entity\TokenInterface $token */
        
        $vars = [
            'title' => $app['translator']->trans($dns . 'register_confirm.title'),
        ];
        
        try {
            $app[$dns . 'token_consumer']('register_confirm', $token);
            $vars['_user'] = $token->getUser();
            $vars['token_type'] = $token->getType();
        } catch(TokenException $e) {
            switch ($e->getCode()) {
                case TokenException::ALREADY_USED:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.already_used', [], 'errors');
                    break;
                case TokenException::TOO_OLD:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.too_old', [], 'errors');
                    break;
                case TokenException::BAD_USE:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.bad_use', [], 'errors');
                    break;
            }
        }
        
        $vars['_require_email_verification'] = $app[$dns . 'firewalls'][$firewall]['require_email_verification'];
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . "/front/register_confirm.html.twig", $vars);
    }
    
    public function recoverPasswordAction(Application $app, Request $request)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');
        
        $message = $request->getSession()->getFlashBag()->get('message');
        $message = reset($message);
        $vars = [
            'title'=> $app['translator']->trans($dns . 'recover_password.title'),
            'message' => $message,
        ];
    
        $data = [];
    
        $recoverPasswordForm = $app->namedForm('recover_password', $data)
            ->add('email', EmailType::class, ['label' => $dns . 'recover_password.labels.email'])
            ->add('submit', SubmitType::class, ['label' => $dns . 'recover_password.labels.submit'])
            ->getForm();
    
        $recoverPasswordForm->handleRequest($request);
        
        if ($recoverPasswordForm->isSubmitted()) {
            if ($recoverPasswordForm->isValid()) {
                $data = $recoverPasswordForm->getData();
                $app[$dns . 'password_recoverer']($request, $data['email']);
                $request->getSession()->getFlashBag()->add('message', $app['translator']->trans($dns . 'recover_password.messages.sent'));
            }
        }
        
        if ($request->isMethod('POST')) {
            return $app->redirect($request->getUri());
        }
            
        $vars['user_recover_password_form'] = $recoverPasswordForm->createView();
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . '/front/recover_password.html.twig', $vars);
    }
    
    public function recoverPasswordConfirmAction(Application $app, Request $request, $token)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');

        $changePasswordException = $request->getSession()->get($dns . 'change_password_exception', []);
        $changePasswordException += [
            'field_errors' => [],
            'message' => null,
        ];
        
        $vars = [
            'title' => 'User Pack Recover Password',
            'error' => $changePasswordException['message'],
        ];
        
        try {
            $app[$dns . 'token_consumer']('recover_password_confirm', $token, false);
            $vars['_user'] = $token->getUser();
            $vars['token_type'] = $token->getType();
        } catch(TokenException $e) {
            switch ($e->getCode()) {
                case TokenException::ALREADY_USED:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.already_used', [], 'errors');
                    break;
                case TokenException::TOO_OLD:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.too_old', [], 'errors');
                    break;
                case TokenException::BAD_USE:
                    $vars['error'] = $app['translator']->trans($dns . 'token.errors.bad_use', [], 'errors');
                    break;
            }
        }
        
        $userChangePasswordForm = $app->namedForm('user_change_password')
            ->add('password', PasswordType::class, ['label' => $dns . 'recover_password_confirm.labels.password'])
            ->add('confirm_password', PasswordType::class, ['label' => $dns . 'recover_password_confirm.labels.confirm_password'])
            ->add('submit', SubmitType::class, ['label' => $dns . 'recover_password_confirm.labels.submit'])
            ->getForm();
        
        $userChangePasswordForm->handleRequest($request);

        if ($userChangePasswordForm->isSubmitted()) {
            if ($userChangePasswordForm->isValid()) {
                $data = $userChangePasswordForm->getData();
                
                $constraints = [];
                $constraints['password'] = new Constraints\Length([
                    'min' => 8,
                    'minMessage' => $dns . 'recover_password_confirm.validators.password.length.min',
                    'max' => 16,
                    'maxMessage' => $dns . 'recover_password_confirm.validators.password.length.max',
                ]);
                $constraints['confirm_password'] = new Constraints\EqualTo([
                    'value' => $data['password'],
                    'message' => $dns . 'recover_password_confirm.validators.confirm_password.equal_to.password',
                ]);
                $constraints = new Constraints\Collection($constraints);
                $violations = $app['validator']->validate($data, $constraints);
                if (count($violations)) {
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
                    $request->getSession()->set($dns . 'change_password_exception', [
                        'message' => $app['translator']->trans($dns . 'recover_password_confirm.errors.validation', [], 'errors'),
                        'field_errors' => $fieldErrors,
                    ]);
                } else {
                    $app[$dns . 'token_consumer']('recover_password_confirm', $token, true);
                    $token->getUser()->setPassword($app[$dns . 'password_encoder']($data['password']));
                    $app['orm.em']->flush();
                    $request->getSession()->getFlashBag()->add('message', $app['translator']->trans($dns . 'recover_password_confirm.messages.password_changed'));
                    $request->getSession()->remove($dns . 'change_password_exception');
                    if ($request->get('_login_route')) {
                        return $app->redirect($app->path($request->get('_login_route')));
                    } else {
                        $app->abort(404);
                    }
                }
            }
        }
        
        if ($request->isMethod('POST')) {
            return $app->redirect($request->getUri());
        }
        
        foreach ($changePasswordException['field_errors'] as $field => $errors) {
            if ($userChangePasswordForm->has($field)) {
                foreach ($errors as $error) {
                    $userChangePasswordForm->get($field)->addError(new FormError($error));
                }
            }
        }
            
        $vars['user_change_password_form'] = $userChangePasswordForm->createView();
        
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . "/front/recover_password_confirm.html.twig", $vars);
    }
    
    public function changePasswordAction(Application $app, Request $request)
    {
        $dns = $this->options['dns'];
        $firewall = $request->get('_firewall');

        $changePasswordException = $request->getSession()->get($dns . 'change_password_exception', []);
        $changePasswordException += [
            'field_errors' => [],
            'message' => null,
        ];
        
        $vars = [
            'title' => 'User Pack Change Password',
            'error' => $changePasswordException['message'],
        ];
        
        $userChangePasswordForm = $app->namedForm('user_change_password')
            ->add('old_password', PasswordType::class, ['label' => $dns . 'change_password.labels.old_password'])
            ->add('password', PasswordType::class, ['label' => $dns . 'change_password.labels.password'])
            ->add('confirm_password', PasswordType::class, ['label' => $dns . 'change_password.labels.confirm_password'])
            ->add('submit', SubmitType::class, ['label' => $dns . 'change_password.labels.submit'])
            ->getForm();
        
        $userChangePasswordForm->handleRequest($request);

        if ($userChangePasswordForm->isSubmitted()) {
            if ($userChangePasswordForm->isValid()) {
                $data = $userChangePasswordForm->getData();
                /** @var \Symfony\Component\Security\Core\User\User $user */
                $user = $app['user'];
                $dbUser = $app[$dns . 'user_loader']($user->getUsername(), false);

                if (!$app[$dns . 'password_validator']($dbUser->getPassword(), $data['old_password'])) {
                    $request->getSession()->getFlashBag()->add('message', $app['translator']->trans($dns . 'change_password.messages.bad_old_password'));
                    $request->getSession()->remove($dns . 'change_password_exception');
                    return $app->redirect($request->getUri());
                }
                unset($data['old_password']);
                
                $constraints = [];
                $constraints['password'] = new Constraints\Length([
                    'min' => 8,
                    'minMessage' => $dns . 'change_password.validators.password.length.min',
                    'max' => 16,
                    'maxMessage' => $dns . 'change_password.validators.password.length.max',
                ]);
                $constraints['confirm_password'] = new Constraints\EqualTo([
                    'value' => $data['password'],
                    'message' => $dns . 'change_password.validators.confirm_password.equal_to.password',
                ]);
                $constraints = new Constraints\Collection($constraints);
                $violations = $app['validator']->validate($data, $constraints);
                if (count($violations)) {
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
                    $request->getSession()->set($dns . 'change_password_exception', [
                        'message' => $app['translator']->trans($dns . 'change_password.errors.validation', [], 'errors'),
                        'field_errors' => $fieldErrors,
                    ]);
                } else {
                    $dbUser->setPassword($app[$dns . 'password_encoder']($data['password']));
                    $app['orm.em']->flush();
                    $request->getSession()->getFlashBag()->add('message', $app['translator']->trans($dns . 'change_password.messages.password_changed'));
                    $request->getSession()->remove($dns . 'change_password_exception');
                }
            }
        }
        
        if ($request->isMethod('POST')) {
            return $app->redirect($request->getUri());
        }
        
        foreach ($changePasswordException['field_errors'] as $field => $errors) {
            if ($userChangePasswordForm->has($field)) {
                foreach ($errors as $error) {
                    $userChangePasswordForm->get($field)->addError(new FormError($error));
                }
            }
        }
            
        $vars['user_change_password_form'] = $userChangePasswordForm->createView();
        
        foreach($this->publicRoutes as $route) {
            $vars[$route] = $request->get($route);
        }
        $vars = call_user_func($app[$dns . 'twig_vars_injector'], 'front:' . __METHOD__, $request, $vars);
        return $app->renderView($this->options['ns'] . "/front/change_password.html.twig", $vars);
    }
}
