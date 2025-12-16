<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'votre@email.com'
                ],
                'label' => 'Email',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2']
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => '••••••••'
                ],
                'label' => 'Mot de passe',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2']
            ])
            ->add('_remember_me', CheckboxType::class, [
                'required' => false,
                'label' => 'Se souvenir de moi',
                'label_attr' => ['class' => 'ml-2 text-sm text-gray-700'],
                'attr' => ['class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
