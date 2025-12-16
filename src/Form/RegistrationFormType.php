<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'Votre pseudo'
                ],
                'label' => 'Pseudo',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                'row_attr' => ['class' => 'mb-1'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un pseudo',
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Votre pseudo doit contenir au moins {{ limit }} caractères',
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'placeholder' => 'votre@email.com'
                ],
                'label' => 'Email',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                'row_attr' => ['class' => 'mb-1'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un email',
                    ]),
                    new Email([
                        'message' => 'L\'email {{ value }} n\'est pas valide.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        'autocomplete' => 'new-password',
                        'placeholder' => '••••••••'
                    ],
                    'label' => 'Mot de passe',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                    'row_attr' => ['class' => 'mb-1'],
                ],
                'second_options' => [
                    'attr' => [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        'autocomplete' => 'new-password',
                        'placeholder' => '••••••••'
                    ],
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                    'row_attr' => ['class' => 'mb-1'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'label_attr' => ['class' => 'ml-2 text-sm text-gray-700'],
                'attr' => ['class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded'],
                'row_attr' => ['class' => 'mb-1'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}