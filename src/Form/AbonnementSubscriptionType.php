<?php

namespace App\Form;

use App\Entity\AbonnementSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AbonnementSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date_sub', DateTimeType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
            ])
            ->add('date_expire', DateTimeType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
            ])
            ->add('active', CheckboxType::class, ['label'=>'Actif', 'required'=>false])
            ->add('is_resiliate', CheckboxType::class, ['label'=>'Resilié', 'required'=>false])
            ->add('date_paid', DateTimeType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
            ])
            ->add('is_paid', CheckboxType::class, ['label'=>'Payé', 'required'=>false])
            ->add('amount')
            ->add('user')
            ->add('abonnement')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AbonnementSubscription::class,
        ]);
    }
}
