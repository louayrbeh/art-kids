<?php

namespace App\Form\FrontOffice;

use App\Entity\Child;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
                'choice_label' => static fn (Child $child): string => sprintf('%s (%d ans)', $child->getFullName(), $child->getAge()),
                'choices' => $options['children'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Reserver',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'children' => [],
        ]);

        $resolver->setAllowedTypes('children', 'array');
    }
}
