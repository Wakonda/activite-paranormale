<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

use Symfony\Component\Validator\Constraints\NotBlank;
use App\Form\Field\SourceEditType;
use App\Entity\Quotation;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class QuotationAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true))
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er) 
							{
								return $er->createQueryBuilder('u')
										->orderBy('u.title', 'ASC');
							},
			))
			->add('family', ChoiceType::class, ['multiple' => false, 'expanded' => false, "required" => true,
					"choices" => [
						"quotation.index.Quotation" => Quotation::QUOTATION_FAMILY,
						"quotation.index.Humor" => Quotation::HUMOR_FAMILY,
						"quotation.index.Proverb" => Quotation::PROVERB_FAMILY,
						"quotation.index.Poem" => Quotation::POEM_FAMILY
					],
					'translation_domain' => 'validators'
			])
			->add('country', EntityType::class, ['class'=>'App\Entity\Region',
					'choice_label'=>'title', 
					'required' => true,
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}])
		    ->add('authorQuotation', Select2EntityType::class, [
				'multiple' => false,
				'remote_route' => 'Biography_Admin_Autocomplete',
				'class' => 'App\Entity\Biography',
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'delay' => 250,
				'cache' => false,
				'required' => true,
				'req_params' => ['locale' => 'parent.children[language]'],
				'language' => $language
			])
            ->add('textQuotation', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
			->add('source', SourceEditType::class, array('required' =>false))
			->add('explanation', TextareaType::class, array('required' => false))
            ->add('tags', TextType::class, ['required' => false])
        ;

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();

			if(($data->isQuotationFamily() or $data->isPoemFamily()) and empty($form->get("authorQuotation")->getData()))
				$form->get('authorQuotation')->addError(new FormError($notBlank->message));

			if($data->isPoemFamily() and empty($form->get("title")->getData()))
				$form->get('title')->addError(new FormError($notBlank->message));

			if($data->isProverbFamily() and empty($form->get("country")->getData()))
				$form->get('country')->addError(new FormError($notBlank->message));
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_quotation_quotationadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Quotation',
			'locale' => 'fr'
		));
	}
}