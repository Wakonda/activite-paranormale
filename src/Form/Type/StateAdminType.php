<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class StateAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label'=>'Texte', 'required' =>true))
            ->add('internationalName', TextType::class, array('label'=>'Nom international', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('displayState', ChoiceType::class, array(
				'choices'   => array(
					'Invisible' => 0,
					'Visible' => 1
				),
				'multiple'  => false,
				'expanded'  => false,
				'placeholder' => false,
				'constraints' => array(new NotBlank())
			))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => false,
				'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
				'constraints' => array(new NotBlank())
			));
    }

    public function getBlockPrefix(): string
    {
        return 'ap_index_stateadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\State',
		));
	}
}