<?php

namespace App\Form;

use App\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('type_reduction', ChoiceType::class, [
                'choices' => [
                    'Type de reduction' => [
                        'Valeur' => 0,
                        'Pourcentage' => 1,
                    ]
                ],
            ])
            ->add('price_reduction')
            ->add('start', DateType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'dateTimeFlatpickr form-control'],
            ])
            ->add('end', DateType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'dateTimeFlatpickr form-control'],
            ])
            ->add('max_usage')
            ->add('current_usage')
            ->add('code')
            // ->add('paniers')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }
}
