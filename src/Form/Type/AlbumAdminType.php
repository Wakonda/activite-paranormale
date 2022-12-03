<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use App\Form\Field\IdentifiersEditType;
use App\Form\Field\ReviewScoresEditType;
use App\Form\Field\SourceEditType;
use App\Form\Field\DatePartialType;
use App\Entity\Artist;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use Symfony\Contracts\Translation\TranslatorInterface;

class AlbumAdminType extends AbstractType
{
	private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];
// dd($language);
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => false))
            ->add('source', SourceEditType::class, array('required' => false))
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
		    ->add('artist', Select2EntityType::class, [
				'multiple' => false,
				'remote_route' => 'Artist_Admin_Autocomplete',
				'class' => Artist::class,
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'req_params' => ['locale' => 'parent.children[language]'],
				'language' => $language,
				"required" => true,
				"constraints" => [new NotBlank()]
			])
			->add('releaseYear', DatePartialType::class, ['required' => false])
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Album_Admin_ShowImageSelectorColorbox'))	
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
				))
			->add('wikidata', TextType::class, array('required' => false))
			->add('tracklist', HiddenType::class, ['mapped' => false, 'required' => false])
            ->add('identifiers', IdentifiersEditType::class, ['required' => false, 'enum' => ["Amazon Standard Identification Number", "MusicBrainz release group ID", "AllMusic album ID", "Spotify album ID"]])
			->add('reviewScores', ReviewScoresEditType::class, ['required' => false, 'enum' => ["Music Story", "AllMusic"]])
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_music_albumadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Album',
			'locale' => 'fr'
		));
	}
}