<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

use App\Form\Field\SourceEditType;
use App\Form\Field\DatePartialType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use App\Entity\EventMessage;

class EventMessageAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label'=>'Texte', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('abstractText', TextareaType::class, array('required' => false))
			->add('dateFrom', DatePartialType::class, array('required' => true, 'constraints' => [new NotBlank()], "mapped" => false, "allow_empty_year" => true))
			->add('dateTo', DatePartialType::class, array('required' => false, "mapped" => false, "allow_empty_year" => true))
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
			->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);},
					'constraints' => array(new NotBlank())
					))
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'required' => true,
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
			->add('illustration', IllustrationType::class, array('required' => true, 'base_path' => 'EventMessage_Admin_ShowImageSelectorColorbox'))
			->add('thumbnail', FileType::class, array('data_class' => null, 'required' => false))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'required' => true,
					'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}))
			->add('longitude', TextType::class, array('required' => false))
			->add('latitude', TextType::class, array('required' => false))
            ->add('type', ChoiceType::class, ['choices' => ['eventMessage.dayMonth.'.ucfirst(EventMessage::BIRTH_DATE_TYPE) => EventMessage::BIRTH_DATE_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::DEATH_DATE_TYPE) => EventMessage::DEATH_DATE_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::EVENT_TYPE) => EventMessage::EVENT_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::CELEBRATION_TYPE) => EventMessage::CELEBRATION_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::CONVENTION_TYPE) => EventMessage::CONVENTION_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::SAINT_TYPE) => EventMessage::SAINT_TYPE, 'eventMessage.dayMonth.'.ucfirst(EventMessage::HOROSCOPE_TYPE) => EventMessage::HOROSCOPE_TYPE], 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators'])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, array('required' => false))
        ;

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event){
			$data = $event->getData();
			$form = $event->getForm();
			
			$dateFrom = $form->get('dateFrom')->getNormData();
			
			$data->setDayFrom($dateFrom["day"]);
			$data->setMonthFrom($dateFrom["month"]);
			$data->setYearFrom($dateFrom["year"]);
			
			$dateTo = $form->get('dateTo')->getNormData();
			
			$data->setDayTo($dateTo["day"]);
			$data->setMonthTo($dateTo["month"]);
			$data->setYearTo($dateTo["year"]);
		});
		
		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
			$data = $event->getData();

			$event->getForm()->get('dateFrom')->get('day')->setData($data->getDayFrom());
			$event->getForm()->get('dateFrom')->get('month')->setData($data->getMonthFrom());
			$event->getForm()->get('dateFrom')->get('year')->setData($data->getYearFrom());

			$event->getForm()->get('dateTo')->get('day')->setData($data->getDayTo());
			$event->getForm()->get('dateTo')->get('month')->setData($data->getMonthTo());
			$event->getForm()->get('dateTo')->get('year')->setData($data->getYearTo());
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_page_eventmessageadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\EventMessage',
			'locale' => 'fr'
		));
	}
}