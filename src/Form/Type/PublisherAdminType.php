<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PublisherAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('website', TextType::class, array('data_class' => null, 'required' => false, 'constraints' => [new Url()]))
			->add('illustration', IllustrationType::class, ['required' => false, 'base_path' => 'Publisher_Admin_ShowImageSelectorColorbox'])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_book_publisheradmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Publisher'
		]);
	}
}