<?php

namespace App\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use App\Form\DataTransformer\DateTimePartialTransformer;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\NotBlank;

class DateTimePartialType extends AbstractType
{
    private $transformer;

    public function __construct(DateTimePartialTransformer $transformer)
    {
        $this->transformer = $transformer;
    }
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
		
		$builder
			->add('day', IntegerType::class, ['required' => false, "attr" => ["placeholder" => "admin.date.Day"], "translation_domain" => "validators"])
			->add('month', IntegerType::class, ['required' => false, "attr" => ["placeholder" => "admin.date.Month"], "translation_domain" => "validators"])
			->add('year', IntegerType::class, ['required' => false, "attr" => ["placeholder" => "admin.date.Year"], "translation_domain" => "validators"])
			->add('hour', TextType::class, ['required' => false, "attr" => ["placeholder" => "admin.date.Hour"], "translation_domain" => "validators"])
			->add('minute', TextType::class, ['required' => false, "attr" => ["placeholder" => "admin.date.Minute"], "translation_domain" => "validators"]);
	
		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($options)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();
			
			$year = $form->get('year')->getData();
			$month = $form->get('month')->getData();
			$day = $form->get('day')->getData();
			$hour = $form->get('hour')->getData();
			$minute = $form->get('minute')->getData();

			if(empty($year) and (!empty($month) or !empty($day)) and !$options["allow_empty_year"]) {
				$form->get('year')->addError(new FormError($notBlank->message));
			}

			if(empty($month) and !empty($day)) {
				$form->get('month')->addError(new FormError($notBlank->message));
			}

			if(empty($hour) and !empty($minute)) {
				$form->get('hour')->addError(new FormError($notBlank->message));
			}

			if($hour != null and !is_numeric($hour)) {
				$form->get('hour')->addError(new FormError("admin.error.Double"));
			}

			if($minute != null and !is_numeric($minute)) {
				$form->get('hour')->addError(new FormError("admin.error.Double"));
			}
			
			if(!empty($month) and !empty($year)) {
				if(empty($day))
					$day = "01";
				
				$month = str_pad($month, 2, "0", STR_PAD_LEFT);
				$day = str_pad($day, 2, "0", STR_PAD_LEFT);
				
				$value = $year."-".$month."-".$day;
				$date = \DateTime::createFromFormat('Y-m-d', $value);

				if(!($date && $date->format('Y-m-d') === $value))
					$form->get('year')->addError(new FormError('This value is not a valid date.'));
			}
		});
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'error_bubbling' => false,
			'allow_empty_year' => false
		]);
	}
}