<?php

namespace App\Form;

use App\Entity\Abonnement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AbonnementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', DateTimeType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'dateTimeFlatpickr form-control'],
            ])
            ->add('end', DateTimeType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'dateTimeFlatpickr form-control'],
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'Type de reduction' => [
                        'Impaye' => 0,
                        'Paye' => 1,
                    ]
                ],
            ])
            ->add('formule')
            ->add('panier')
            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Abonnement::class,
        ]);
    }
}
