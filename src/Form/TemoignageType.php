<?php

namespace App\Form;

use App\Entity\Temoignage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class TemoignageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creation', DateType::class, [
                'input'=>'datetime',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'dateTimeFlatpickr form-control'],
            ])
            ->add('name')
            ->add('location')
            ->add('video')
            ->add('text',TextareaType::class, array('attr' => array('class' => 'ckeditor')))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Temoignage::class,
        ]);
    }
}
