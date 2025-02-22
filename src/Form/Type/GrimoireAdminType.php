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

use App\Form\Field\SourceEditType;

class GrimoireAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('surTheme', EntityType::class, array('class'=>'App\Entity\SurThemeGrimoire', 
					'choice_label'=>'title', 
					'required' => true,
					'group_by' => 'getMenuGrimoireTitle',
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) use ($language) {
						return $er->createQueryBuilder('u')
								  ->leftJoin('u.language', 'l')
								  ->where('l.abbreviation = :abbreviation')
								  ->setParameter('abbreviation', $language)
								  ->andWhere("u.parentTheme IS NOT NULL")
								  ->orderBy('u.title', 'ASC');
					}
			))
            ->add('source', SourceEditType::class, ['required' => false])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				}
			))
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Grimoire_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_witchcraft_grimoireadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Grimoire',
			'locale' => 'fr'
		));
	}
}