<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Url;
use Doctrine\ORM\EntityRepository;

class DealAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['constraints' => [new NotBlank()]])
            ->add('link', TextType::class, ['constraints' => [new NotBlank(), new Url()]])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
			))
			->add('active', CheckboxType::class, ['required' => true])
            ->add('photo', FileType::class, ["required" => true, 'data_class' => null])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'data' => $builder->getData()->getPhoto()])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_index_dealtype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Deal',
		]);
	}
}