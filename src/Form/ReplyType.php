<?php

namespace App\Form;

use App\Entity\LineDiscussionReply;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre réponse',
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-700 mb-2'],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'rows' => 4,
                    'placeholder' => 'Écrivez votre réponse...',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La réponse est obligatoire']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'La réponse doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LineDiscussionReply::class,
        ]);
    }
}