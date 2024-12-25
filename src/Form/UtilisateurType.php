<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le login ne peut pas être vide.']),
                    new Length([
                        'min' => 4,
                        'max' => 20,
                        'minMessage' => 'Le login doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le login ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'minlength' => 4,
                    'maxlength' => 20,
                ],
            ])
            ->add('adresseEmail', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse email ne peut pas être vide.']),
                    new Email(['message' => 'L\'adresse email n\'est pas valide.']),
                ],
            ]);

        if (!$options['is_edit']) {
            $builder->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe ne peut pas être vide.']),
                    new Length([
                        'min' => 8,
                        'max' => 30,
                        'minMessage' => 'Le mot de passe doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,30}$/',
                        'message' => 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.',
                    ]),
                ],
                'attr' => [
                    'minlength' => 8,
                    'maxlength' => 30,
                ],
            ]);
        }

        $builder
            ->add('code', TextType::class, [
                'label' => 'Code du profil',
                'required' => false,
            ])
            ->add('visible', CheckboxType::class, [
                'label' => 'Visible',
                'required' => false,
            ]);

        if ($options['is_edit']) {
            $builder
                ->add('telephone', TextType::class, [
                    'required' => false,
                    'constraints' => [
                        new Length(['max' => 10, 'maxMessage' => 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.']),
                        new Regex([
                            'pattern' => '/^\d{10}$/',
                            'message' => 'Le numéro de téléphone doit contenir exactement 10 chiffres.',
                        ]),
                    ],
                    'attr' => ['maxlength' => 10],
                ]);
            if ($options['isCurrentUser']) {
                $builder
                    ->add('oldPassword', PasswordType::class, [
                        'mapped' => false,
                        'required' => false,
                        'label' => 'Ancien Mot de Passe',
                    ])
                    ->add('newPassword', PasswordType::class, [
                        'mapped' => false,
                        'required' => false,
                        'label' => 'Nouveau Mot de Passe',
                    ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
            'isCurrentUser' => false,
        ]);
    }
}