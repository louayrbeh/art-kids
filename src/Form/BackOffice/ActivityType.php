<?php

namespace App\Form\BackOffice;

use App\Entity\Activity;
use App\Entity\Category;
use App\Enum\ActivityStatusEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('image', TextType::class, [
                'label' => 'Image',
                'required' => false,
            ])
            ->add('dateActivite', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de debut',
                'widget' => 'single_text',
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
            ])
            ->add('capaciteMax', IntegerType::class, [
                'label' => 'Capacite max',
            ])
            ->add('ageMin', IntegerType::class, [
                'label' => 'Age min',
            ])
            ->add('ageMax', IntegerType::class, [
                'label' => 'Age max',
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'required' => false,
                'currency' => 'EUR',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouverte' => ActivityStatusEnum::OUVERTE,
                    'Complete' => ActivityStatusEnum::COMPLETE,
                    'Annulee' => ActivityStatusEnum::ANNULEE,
                    'Terminee' => ActivityStatusEnum::TERMINEE,
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'label' => 'Categorie',
                'choice_label' => 'nom',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
