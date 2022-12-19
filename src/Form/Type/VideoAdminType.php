<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validation;

use App\Form\TagsAdminType;
use App\Form\Type\FileSelectorType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Entity\TagWord;

class VideoAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["locale"];
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('platform', ChoiceType::class, array(
				'choices'   => array(
					'AP' => 'AP',
					'Dailymotion' => 'Dailymotion',
					'Facebook' => 'Facebook',
					'Instagram' => 'Instagram',
					'Rutube' => 'Rutube',
					'Twitter' => 'Twitter',
					'Youtube' => 'Youtube',
					'Other' => 'Other'
				),
				'multiple'  => false,
				'expanded'  => false,
				'constraints' => array(new NotBlank())
			))
            ->add('mediaVideo', FileType::class, array('data_class' => null, 'required' => false))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('embeddedCode', TextareaType::class, array('required' => false))
			->add('hourDuration', IntegerType::class, array('mapped' => false))
			->add('minuteDuration', IntegerType::class, array('mapped' => false))
			->add('secondDuration', IntegerType::class, array('mapped' => false))
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
            ->add('publicationDate', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('theme', ThemeEditType::class, ['locale' => $language, 'label' => 'Thème', 'class'=>'App\Entity\Theme', 'constraints' => [new NotBlank()], 'required' => true])
            ->add('photo', FileType::class, array('data_class' => null, 'required' => false))
			->add('pseudoUsed', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
					))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}))
			->add('mediaVideo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => "Video_Admin_ChooseExistingFile", 'data' => $builder->getData()->getMediaVideo()])
			->add('available', CheckboxType::class, array('required' => true))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => 'Video_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getPhoto()))
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
				'req_params' => ['locale' => 'parent.children[language]'],
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
		;
		
		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
			$data = $event->getData();
			$form = $event->getForm();
			
			$duration = $data->getDuration();
			$duration_array = array_reverse(explode(":", $duration));
			
			$second = (isset($duration_array[0]) and !empty($duration_array[0])) ? $duration_array[0] : 0;
			$minute = (isset($duration_array[1]) and !empty($duration_array[1])) ? $duration_array[1] : 0;
			$hour = (isset($duration_array[2])  and !empty($duration_array[2])) ? $duration_array[2] : 0;

			$event->getForm()->get('hourDuration')->setData($hour);
			$event->getForm()->get('minuteDuration')->setData($minute);
			$event->getForm()->get('secondDuration')->setData($second);
		});
		
		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event){
			$data = $event->getData();
			$form = $event->getForm();
			
			$data->setEmbeddedCode($data->resizeVideo());

			// For Video
			$mediaVideo = $form->get('mediaVideo')->getNormData();
			$embeddedCode = $form->get('embeddedCode')->getNormData();
			$existingFile = $form->get('existingFile')->getNormData();

			if($form->get('platform')->getNormData() != "AP")
			{
				if(empty($embeddedCode))
					$form->get('embeddedCode')->addError(new FormError("admin.error.NotBlank"));
				else
				{
					if($form->get('platform')->getNormData() == "Twitter")
					{
						$dom = new \DomDocument();

						$dom->loadHTML($data->getEmbeddedCode(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

						$blockquote = $dom->getElementsByTagName('blockquote');

						if($blockquote->length > 0)
						{
							$blockquote = $blockquote->item(0);
							$classBlockquote = $blockquote->getAttribute("class");
							$blockquote->setAttribute("class", $classBlockquote." tw-align-center");
							
							$data->setEmbeddedCode($dom->saveHTML());
						}
					}
				}
			}
			
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
		
			$data->setDuration(implode(':', array($hourDuration, $minuteDuration, $secondDuration)));
		});
    }
	
    public function getBlockPrefix()
    {
        return 'ap_video_videoadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Video',
			'locale' => 'fr'
		));
	}
}