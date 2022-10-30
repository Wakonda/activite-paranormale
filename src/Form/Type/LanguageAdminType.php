<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LanguageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('constraints' => array(new NotBlank())))
            ->add('abbreviation', TextType::class, array('constraints' => array(new NotBlank())))
            ->add('logo', FileType::class, array("required" => true, 'data_class' => null))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'data' => $builder->getData()->getLogo()))
			->add('direction', ChoiceType::class, array('constraints' => array(new NotBlank()), 'expanded' => true, 'multiple' => false, 'choices' => array('language.admin.RightToLeft' => 'rtl', 'language.admin.LeftToRight' => 'ltr'), 'translation_domain' => 'validators'))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_index_languagetype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Language',
		));
	}
}