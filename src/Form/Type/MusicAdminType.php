<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use App\Form\Field\SourceEditType;
use App\Form\Field\IdentifiersEditType;
use App\Entity\Artist;
use App\Entity\Album;

class MusicAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["language"];

		if(!empty($builder->getData()->getAlbum()))
			$language = $builder->getData()->getAlbum()->getLanguage();

        $builder
            ->add('text', TextareaType::class, ['required' => false])
            ->add('source', SourceEditType::class, ['required' => false])
			->add('language', EntityType::class, array('class'=>'App\Entity\Language',
					'choice_label'=>'title',
					'mapped' => false,
					'required' => true,
					"data" => $language,
					'query_builder' => function(EntityRepository $er) 
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
					'constraints' => array(new NotBlank())
				))
		    ->add('album', Select2EntityType::class, [
				'multiple' => false,
				'remote_route' => 'Album_Admin_Autocomplete',
				'class' => Album::class,
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'req_params' => ['locale' => 'parent.children[language]']
			])
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
				'req_params' => ['locale' => 'parent.children[language]']
			])
			->add('linkMusic', ChoiceType::class, [
				'choices'  => ["admin.index.Album" => "album", "admin.index.Artist" => "artist"],
				'data' => (!empty($builder->getData()->getArtist()) ? "artist" : (!empty($builder->getData()->getAlbum()) ? "album" : null)),
				'mapped' => false,
				'translation_domain' => 'validators', "attr" => ["class" => "form-control"]
			])
            ->add('musicPieceFile',  FileType::class, array('data_class' => null, 'required' => true))
			->add('music_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => "Music_Admin_ChooseExistingFile", 'data' => $builder->getData()->getMusicPieceFile()])
            ->add('musicPiece', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('hourDuration', IntegerType::class, array('mapped' => false))
			->add('minuteDuration', IntegerType::class, array('mapped' => false))
			->add('secondDuration', IntegerType::class, array('mapped' => false))
            ->add('embeddedCode', TextareaType::class, array('required' => false))
			->add('wikidata', TextType::class, ['required' => false])
            ->add('identifiers', IdentifiersEditType::class, ['required' => false, 'enum' => ["ISRC", "AllMusic song ID", "MusicBrainz recording ID", "YouTube video ID"]])
        ;

		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
			$data = $event->getData();
			$form = $event->getForm();
			
			$duration = $data->getLength();
			$duration_array = array_reverse(explode(":", $duration));
			
			$second = (isset($duration_array[0]) and !empty($duration_array[0])) ? $duration_array[0] : 0;
			$minute = (isset($duration_array[1]) and !empty($duration_array[1])) ? $duration_array[1] : 0;
			$hour = (isset($duration_array[2])  and !empty($duration_array[2])) ? $duration_array[2] : 0;

			$event->getForm()->get('hourDuration')->setData($hour);
			$event->getForm()->get('minuteDuration')->setData($minute);
			$event->getForm()->get('secondDuration')->setData($second);
		});
		
		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($builder) {
			$data = $event->getData();
			$form = $event->getForm();

			// For Duration
			$hourDuration = $form->get('hourDuration')->getNormData();
			$minuteDuration = $form->get('minuteDuration')->getNormData();
			if(!empty($minuteDuration))
			{
				if(intval($minuteDuration) > 59)
					$form->get('secondDuration')->addError(new FormError('admin.error.MinutesLimit'));
			}

			$secondDuration = $form->get('secondDuration')->getNormData();
			if(!empty($secondDuration))
			{
				if(intval($secondDuration) > 59)
					$form->get('secondDuration')->addError(new FormError('admin.error.SecondesLimit'));
			}
			
			if(empty($hourDuration) and empty($minuteDuration) and empty($secondDuration))
				$form->get('secondDuration')->addError(new FormError('admin.error.OneThreeFieldNotEmpty'));
		
			$data->setLength(implode(':', array($hourDuration, $minuteDuration, $secondDuration)));
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_music_musicadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Music',
			'language' => null,
		));
	}
}