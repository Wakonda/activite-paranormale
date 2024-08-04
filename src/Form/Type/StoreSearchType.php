<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Stores\Store;

class StoreSearchType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->setMethod('GET');

		$categories = [
				$this->translator->trans("store.index.".ucfirst(Store::BOOK_CATEGORY), [], 'validators') => Store::BOOK_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::CLOTH_CATEGORY), [], 'validators') => Store::CLOTH_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::ALBUM_CATEGORY), [], 'validators') => Store::ALBUM_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::MOVIE_CATEGORY), [], 'validators') => Store::MOVIE_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::TELEVISION_SERIE_CATEGORY), [], 'validators') => Store::TELEVISION_SERIE_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::WITCHCRAFT_TOOL_CATEGORY), [], 'validators') => Store::WITCHCRAFT_TOOL_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::FUNNY_CATEGORY), [], 'validators') => Store::FUNNY_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::GOTHIC_CLOTH_CATEGORY), [], 'validators') => Store::GOTHIC_CLOTH_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::MUG_CATEGORY), [], 'validators') => Store::MUG_CATEGORY,
				$this->translator->trans("store.index.".ucfirst(Store::STICKER_CATEGORY), [], 'validators') => Store::STICKER_CATEGORY
			];

		ksort($categories);

        $builder
			->add('category', ChoiceType::class, ['choices' => $categories, 'expanded' => false, 'multiple' => false, "required" => false])
			->add('platform', ChoiceType::class, ['choices' => [ucfirst(Store::ALIEXPRESS_PLATFORM) => Store::ALIEXPRESS_PLATFORM, ucfirst(Store::AMAZON_PLATFORM) => Store::AMAZON_PLATFORM, ucfirst(Store::SPREADSHOP_PLATFORM) => Store::SPREADSHOP_PLATFORM, ucfirst(Store::TEMU_PLATFORM) => Store::TEMU_PLATFORM], 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators']);
		;
    }

    public function getBlockPrefix()
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		]);
	}
}