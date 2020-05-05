<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('price')
            ->add('old_price')
            ->add('quantity')
            ->add('description',TextareaType::class, array('attr' => array('class' => 'ckeditor')))
            ->add('image', FileType::class, [
                'required'=> false,
                'attr' => [
                    'accept' => 'image/*'
                ],
                'data_class' => null
            ])
            ->add('type',ChoiceType::class, array(
                    'choices'  => array(
                        'Simple' => 0,
                        'Reccurent' => 1,
                    ))
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
