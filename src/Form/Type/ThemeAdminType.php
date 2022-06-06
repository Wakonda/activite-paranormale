<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\Type\FileSelectorType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ThemeAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
					'choice_label'=>'title',
					'required' => true,
					'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
					'constraints' => array(new NotBlank())
					))
            ->add('pdfTheme', FileType::class, array('data_class' => null, 'required' => false))
			->add('internationalName', TextType::class, array('label'=>'Nom international', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('surTheme', EntityType::class, array('class'=>'App\Entity\SurTheme', 
				'choice_label'=>'title',
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(\App\Repository\SurThemeRepository $repository) use($language) { return $repository->getSurThemeByLanguage($language);}
			))
			->add('photo', FileType::class, array('data_class' => null, 'required' => true))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => 'Theme_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getPhoto()))
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_index_themeadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Theme',
			'locale' => 'fr'
		));
	}
}