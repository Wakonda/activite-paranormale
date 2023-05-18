<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TagsAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tags', TextType::class, ['mapped' => false, 'constraints' => [new NotBlank()]])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_tags_tagsadmintype';
    }
}