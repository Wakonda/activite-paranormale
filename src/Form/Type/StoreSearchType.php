<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use App\Entity\Stores\Store;

class StoreSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->setMethod('GET');
        $builder
			->add('category', ChoiceType::class, [
			'choices' => [
				"store.index.".ucfirst(Store::BOOK_CATEGORY) => Store::BOOK_CATEGORY,
				"store.index.".ucfirst(Store::CLOTH_CATEGORY) => Store::CLOTH_CATEGORY,
				"store.index.".ucfirst(Store::ALBUM_CATEGORY) => Store::ALBUM_CATEGORY,
				"store.index.".ucfirst(Store::MOVIE_CATEGORY) => Store::MOVIE_CATEGORY,
				"store.index.".ucfirst(Store::TELEVISION_SERIE_CATEGORY) => Store::TELEVISION_SERIE_CATEGORY,
				"store.index.".ucfirst(Store::WITCHCRAFT_TOOL_CATEGORY) => Store::WITCHCRAFT_TOOL_CATEGORY
			], 'expanded' => false, 'multiple' => false, "required" => false, 'translation_domain' => 'validators']);
		;
    }

    public function getBlockPrefix()
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		));
	}
}