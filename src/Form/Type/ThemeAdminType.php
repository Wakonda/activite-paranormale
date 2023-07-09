<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\Type\FileSelectorType;
use App\Form\Field\SourceEditType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ThemeAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['label'=>'Titre', 'required' =>true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => false])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, ['required' => false])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()],
			])
            ->add('pdfTheme', FileType::class, ['data_class' => null, 'required' => false])
			->add('internationalName', TextType::class, ['label'=>'Nom international', 'required' =>true, 'constraints' => [new NotBlank()]])
			->add('parentTheme', EntityType::class, ['class'=>'App\Entity\Theme', 
				'choice_label'=>'title',
				'required' => false,
				'query_builder' => function(\App\Repository\ThemeRepository $repository) use($language) { return $repository->getParentThemeByLanguage($language);}
			])
			->add('photo', FileType::class, ['data_class' => null, 'required' => true])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => 'Theme_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getPhoto()])
			->add('pdf_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getPdfTheme()])
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_index_themeadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Theme',
			'locale' => 'fr'
		]);
	}
}