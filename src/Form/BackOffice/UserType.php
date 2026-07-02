<?php

namespace App\Form\BackOffice;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'];
        $selectedRole = $user instanceof User && $user->isAdmin()
            ? UserRole::ROLE_ADMIN->value
            : UserRole::ROLE_PARENT->value;

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prenom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Parent' => UserRole::ROLE_PARENT->value,
                    'Administrateur' => UserRole::ROLE_ADMIN->value,
                ],
                'multiple' => false,
                'expanded' => true,
                'mapped' => false,
                'required' => true,
                'data' => $selectedRole,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['require_password'] ? 'Mot de passe' : 'Nouveau mot de passe',
                'mapped' => false,
                'required' => $options['require_password'],
                'constraints' => array_filter([
                    $options['require_password'] ? new NotBlank(message: 'Le mot de passe est obligatoire.') : null,
                    new Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caracteres.'),
                ]),
                'empty_data' => '',
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Compte actif',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => false,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
