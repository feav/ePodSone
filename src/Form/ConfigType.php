<?php

namespace App\Form;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ConfigType extends AbstractType
{
    private $list_key;

    public function __construct()
    {
        $configs = (new Config())->getKeyName();
        $this->list_key = [];
        foreach ($configs as $key => $value) {
            $this->list_key[$value['description']] = $value['key'];
        }
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder
            ->add('mkey', ChoiceType::class, [
                'choices' =>$this->list_key
            ])
            ->add('value',TextareaType::class, array('attr' => array('class' => 'ckeditor')))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
