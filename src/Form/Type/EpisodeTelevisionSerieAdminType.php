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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use App\Form\Field\SourceEditType;
use App\Form\Field\IdentifiersEditType;
use App\Entity\Movies\TelevisionSerieBiography;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use App\Entity\TagWord;

class EpisodeTelevisionSerieAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $builder->getData()->getTelevisionSerie()->getLanguage()->getId();

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('synopsis', TextareaType::class, array('required' => false))
            ->add('duration', IntegerType::class, array('required' => false))
            ->add('season', IntegerType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('episodeNumber', IntegerType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('fullStreaming', TextareaType::class, array('required' => false))
            ->add('source', SourceEditType::class, array('required' => false))
            ->add('releaseDate', DateType::class, array('required' => false, 'widget' => 'single_text'))
			->add('televisionSerie', EntityType::class, array('class'=>'App\Entity\Movies\TelevisionSerie',
					'choice_label'=>'title',
					'required' => true,
					'query_builder' => function(EntityRepository $er) use ($builder)
					{
						return $er->createQueryBuilder('u')
								  ->where('u.id = :id')
								  ->setParameter("id", $builder->getData()->getTelevisionSerie());
					},
					'constraints' => array(new NotBlank())
			))
			->add('episodeTelevisionSerieBiographies', CollectionType::class, array("label" => false, "required" => false, 'entry_type' => BiographiesAdminType::class, "allow_add" => true, "allow_delete" => true, "entry_options" => ["label" => false, "data_class" => TelevisionSerieBiography::class, "query_parameters" => ["locale" => $language]]))
		    ->add('tags', Select2EntityType::class, [
				'multiple' => true,
				'allow_add' => [
					'enabled' => true,
					'new_tag_text' => '',
					'new_tag_prefix' => '__',
					'tag_separators' => '[","]'
				],
				'remote_route' => 'TagWord_Admin_Autocomplete',
				'class' => TagWord::class,
				"query_parameters" => ["locale" => $language],
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'language' => $language,
				'mapped' => false,
				'data' => $builder->getData(),
				"transformer" => \App\Form\DataTransformer\TagWordTransformer::class
			])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('identifiers', IdentifiersEditType::class, ['required' => false, 'enum' => \App\Service\Identifier::getTelevisionSerieIdentifiers()])
		;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_movie_televisionserieadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Movies\EpisodeTelevisionSerie'
		));
	}
}