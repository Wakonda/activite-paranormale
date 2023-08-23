<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class GenerateLinkStoreAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('asin', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('asinUrl', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_generatelinkstore_usefullinkadmintype';
    }
}