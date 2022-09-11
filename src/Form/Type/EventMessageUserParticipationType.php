<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class EventMessageUserParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["language"];
		$user = $options["user"];
		$securityUser = $options["securityUser"];

        $builder
            ->add('title', TextType::class, array('label' => 'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
			->add('dateTo', DateType::class, array('required' => true, 'mapped' => false, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
			->add('dateFrom', DateType::class, array('required' => true, 'mapped' => false, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label' => 'Témoignage', 'required' => true, 'constraints' => array(new NotBlank())))
			->add('validate', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
			))
			->add('illustration', FileType::class, array('data_class' => null, 'required' => false))
			->add('longitude', HiddenType::class, array('required' => false))
			->add('latitude', HiddenType::class, array('required' => false))
			;

		if(!is_object($user))
		{
			$builder
				->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())));
		}
		else
		{
			$builder->add('isAnonymous', ChoiceType::class, array(
				'choices'   => array(
					'eventMessage.new.PublishedAnonymously' => 1,
					'eventMessage.new.PostedWithMyUserAccount' => 0,
				),
				'multiple'  => false,
				'expanded'  => false,
				'constraints' => array(new NotBlank()),
				'placeholder' => false,
				'data' => 0,
				'translation_domain' => 'validators'
			));
		}

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event)
		{
			$data = $event->getData();
			$form = $event->getForm();

			if(is_object($data->getPhoto()))
			{
				$formatArray = array('image/png', 'image/jpeg', 'image/gif');

				if(!in_array($data->getPhoto()->getMimeType(), $formatArray))
					$form->get('photo')->addError(new FormError('eventMessage.error.FileFormat'));

				if($data->getPhoto()->getSize() > $data->getPhoto()->getMaxFilesize())
					$form->get('photo')->addError(new FormError('eventMessage.error.FileSizeError'));
			}
			
			if (!empty($dateFrom = $form->get('dateFrom')->getNormData())) {
				$data->setDayFrom($dateFrom->format("d"));
				$data->setMonthFrom($dateFrom->format("m"));
				$data->setYearFrom($dateFrom->format("Y"));
			}
			
			if (!empty($dateTo = $form->get('dateTo')->getNormData())) {
				$data->setDayTo($dateTo->format("d"));
				$data->setMonthTo($dateTo->format("m"));
				$data->setYearTo($dateTo->format("Y"));
			}

			if($dateFrom > $dateTo)
				$form->get('dateFrom')->addError(new FormError('eventMessage.error.StartDateSuperiorEndDate'));
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_page_eventmessageuserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\EventMessage',
			'translation_domain' => 'validators',
			'language' => 'fr',
			'user' => null,
			'securityUser' => null
		));
	}
}