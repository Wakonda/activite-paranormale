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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Form\EventListener\InternationalNameFieldListener;
use App\Form\Field\SourceEditType;

use Symfony\Contracts\Translation\TranslatorInterface;

class WitchcraftToolAdminType extends AbstractType
{
	private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
				))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
				'choice_label'=>'title', 
				'required' => true,
				'constraints' => array(new NotBlank()),
				'choice_attr' => function($val, $key, $index) {
					return ['data-intl' => $val->getInternationalName()];
				},
				'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}))
			->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('witchcraftThemeTool', EntityType::class, array(
					'class'=>'App\Entity\WitchcraftThemeTool',
					'choice_label'=>'title',
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\WitchcraftThemeToolRepository $repository) use ($language)
					{ 
						return $repository->createQueryBuilder("p")->innerjoin("p.language", "l")->where("l.abbreviation = :language")->setParameter("language", $language)->orderBy("p.title", "ASC");
					}
			))
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'WitchcraftTool_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()))
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, array('required' => false));

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

    public function getBlockPrefix()
    {
        return 'ap_witchcraft_witchcrafttooladmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\WitchcraftTool',
			'locale' => 'fr'
		));
	}
}