<?php

namespace App\Form\BackOffice;

use App\Entity\Activity;
use App\Entity\Child;
use App\Entity\Reservation;
use App\Enum\ReservationStatusEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('child', EntityType::class, [
                'class' => Child::class,
                'label' => 'Enfant',
                'choice_label' => static fn (Child $child): string => $child->getFullName(),
            ])
            ->add('activity', EntityType::class, [
                'class' => Activity::class,
                'label' => 'Activite',
                'choice_label' => static fn (Activity $activity): string => $activity->getTitre(),
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => ReservationStatusEnum::EN_ATTENTE,
                    'Confirmee' => ReservationStatusEnum::CONFIRMEE,
                    'Annulee' => ReservationStatusEnum::ANNULEE,
                    'Terminee' => ReservationStatusEnum::TERMINEE,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
