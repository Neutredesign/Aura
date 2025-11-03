<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'required' => false,
                'label' => 'Pseudonyme',
                'attr' => ['maxlength' => 50, 'placeholder' => 'Ton pseudo'],
            ])
            ->add('avatarFile', FileType::class, [
                'mapped' => false,           // IMPORTANT: ce champ n'est pas une propriété de User
                'required' => false,
                'label' => 'Photo de profil',
                'constraints' => [
                    new File([
                        'maxSize'        => '3M',
                        'mimeTypes'      => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPG, WEBP (max 3 Mo).',
                    ]),
                ],
                'help' => 'PNG, JPG, WEBP — 3 Mo max',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
