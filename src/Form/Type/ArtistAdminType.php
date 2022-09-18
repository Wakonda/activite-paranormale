<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use App\Form\Field\SourceEditType;
use App\Entity\ArtistBiography;

class ArtistAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
		
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('genre', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('website', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Artist_Admin_ShowImageSelectorColorbox'))
			->add('biography', TextareaType::class, array('required' => false))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label'=>'title',
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er)
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
				))
            ->add('genre', EntityType::class, array('class'=>'App\Entity\MusicGenre',
					'choice_label'=>'title',
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) use ($language)
						{
							return $er->createQueryBuilder('u')
									  ->orderBy('u.title', 'ASC')
									  ->innerjoin("u.language", "l")
									  ->where("l.abbreviation = :locale")
									  ->setParameter("locale", $language);
						},
				))
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 
					'choice_label'=>'title', 
					'required' => false,
					'query_builder' => function(\App\Repository\CountryRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
            ->add('source', SourceEditType::class, array('required' => false))
			->add('wikidata', TextType::class, ['required' => false])
			->add('artistBiographies', CollectionType::class, array("label" => false, "required" => false, 'entry_type' => ArtistBiographiesAdminType::class, "allow_add" => true, "allow_delete" => true, "entry_options" => ["label" => false, "data_class" => ArtistBiography::class, "language" => $language, "req_params" => ["locale" => $this->getBlockPrefix()."[language]"]]))
        ;
		
		$builder->add('internationalName', HiddenType::class, array('required' => true, 'constraints' => array(new NotBlank())));
		
		$builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){
			$data = $event->getData();
			
			if(empty($data["internationalName"]) and !empty($title = $data["title"])) {
				$generator = new \Ausi\SlugGenerator\SlugGenerator;
				$data["internationalName"] = $generator->generate($title).uniqid();
				$event->setData($data);
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_music_artistadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Artist',
			'locale' => 'fr'
		));
	}
}