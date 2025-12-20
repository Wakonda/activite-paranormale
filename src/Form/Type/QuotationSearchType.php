<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use App\Entity\Quotation;

class QuotationSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('keywords', TextType::class, ['required' => false])
			->add('sort', ChoiceType::class, ['required' => false, 'choices' => ["quotation.search.PublicationDateUp" => "id#asc", "quotation.search.PublicationDateDown" => "id#desc"], 'translation_domain' => 'validators']);

		if($options["family"] == Quotation::PROVERB_FAMILY) {
			$builder->add('country', EntityType::class, [
					'class'=>'App\Entity\Region',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) {
						return $repository->getCountryByLanguage($language);
					}]);
		}
    }
    public function getBlockPrefix(): string
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'family' => null,
			'validation_groups' => ['form_validation_only']
		));
	}
}