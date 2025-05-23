<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class LanguageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('abbreviation', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('logo', FileType::class, ["required" => true, 'data_class' => null])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'data' => $builder->getData()->getLogo()])
			->add('direction', ChoiceType::class, ['constraints' => [new NotBlank()], 'expanded' => true, 'multiple' => false, 'choices' => ['language.admin.RightToLeft' => 'rtl', 'language.admin.LeftToRight' => 'ltr'], 'translation_domain' => 'validators'])
			->add('current', CheckboxType::class, ["required" => false])
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