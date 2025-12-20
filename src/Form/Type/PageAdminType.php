<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class PageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label'=>'Texte', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('internationalName', TextType::class, array('label'=>'Nom international', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
				'constraints' => array(new NotBlank())
			))
			->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
				'choice_label'=>'title', 
				'required' => true,
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);},
				'constraints' => array(new NotBlank())
			))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
				'choice_label'=>'title', 
				'required' => true,
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);},
				'constraints' => array(new NotBlank())
			))
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_page_pageadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Page',
			'locale' => 'fr'
		));
	}
}