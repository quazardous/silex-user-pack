<?php

namespace Quazardous\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * 
 * Allow value rendering for password fields.
 *
 */
class PasswordTypeExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['always_empty']) {
            $view->vars['value'] = '';
        } else {
            $view->vars['value'] = $form->getData();
        }
    }
    
    public function getExtendedType()
    {
        return PasswordType::class;
    }
}
