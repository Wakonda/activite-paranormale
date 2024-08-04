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
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormError;
use Symfony\Contracts\Translation\TranslatorInterface;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

use App\Service\Currency;
use App\Entity\Stores\Store;
use App\Entity\BookEdition;
use App\Entity\WitchcraftTool;
use App\Entity\Album;
use App\Entity\Movies\Movie;
use App\Entity\Movies\TelevisionSerie;
use App\Entity\Stores\BookStore;
use App\Entity\Stores\AlbumStore;
use App\Entity\Stores\MovieStore;
use App\Entity\Stores\TelevisionSerieStore;
use App\Entity\Stores\WitchcraftToolStore;
use App\Form\Field\StoreEditType;

class StoreAdminType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

		$fields = [];
		
		$builder
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()]
			])
			->add('photo',  FileType::class, ['data_class' => null, 'required' => false])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => "Store_Admin_ShowImageSelectorColorbox", 'data' => $builder->getData()->getPhoto()]);

		switch($options["data_class"])
		{
			case BookStore::class:
				$builder
					->add('book', Select2EntityType::class, [
						'multiple' => false,
						'remote_route' => 'Store_Admin_Autocomplete',
						'class' => BookEdition::class,
						'req_params' => ['locale' => 'parent.children[language]'],
						'page_limit' => 10,
						'primary_key' => 'id',
						'allow_clear' => true,
						'delay' => 250,
						'language' => $language,
						'constraints' => [new NotBlank()]
					]);
				break;
			case AlbumStore::class:
				$fields = ["format" => ["type" => "choice", "choices" => ["CD" => "store.characteristic.Cd", "store.characteristic.VinylDisc" => "vinylDisc"], "label" => $this->translator->trans("store.admin.Format", [], "validators"), "required" => true]];
				$builder
					->add('album', Select2EntityType::class, [
						'multiple' => false,
						'remote_route' => 'Store_Admin_Autocomplete',
						'class' => Album::class,
						'req_params' => ['locale' => 'parent.children[language]'],
						'page_limit' => 10,
						'primary_key' => 'id',
						'allow_clear' => true,
						'delay' => 250,
						'language' => $language,
						'constraints' => [new NotBlank()]
					]);
				break;
			case MovieStore::class:
				$fields = ["format" => ["type" => "choice", "choices" => ["store.characteristic.Dvd" => "dvd", "store.characteristic.BluRay" => "bluRay"], "label" => $this->translator->trans("store.admin.Format", [], "validators"), "required" => true]];
				$builder
					->add('movie', Select2EntityType::class, [
						'multiple' => false,
						'remote_route' => 'Store_Admin_Autocomplete',
						'class' => Movie::class,
						'req_params' => ['locale' => 'parent.children[language]'],
						'page_limit' => 10,
						'primary_key' => 'id',
						'allow_clear' => true,
						'delay' => 250,
						'language' => $language,
						'constraints' => [new NotBlank()]
					]);
				break;
			case TelevisionSerieStore::class:
				$fields = ["format" => ["type" => "choice", "choices" => ["store.characteristic.Dvd" => "dvd", "store.characteristic.BluRay" => "bluRay"], "label" => $this->translator->trans("store.admin.Format", [], "validators"), "required" => true]];
				$builder
					->add('televisionSerie', Select2EntityType::class, [
						'multiple' => false,
						'remote_route' => 'Store_Admin_Autocomplete',
						'class' => TelevisionSerie::class,
						'req_params' => ['locale' => 'parent.children[language]'],
						'page_limit' => 10,
						'primary_key' => 'id',
						'allow_clear' => true,
						'delay' => 250,
						'language' => $language,
						'constraints' => [new NotBlank()]
					]);
				break;
			case WitchcraftToolStore::class:
				$builder
					->add('witchcraftTool', Select2EntityType::class, [
						'multiple' => false,
						'remote_route' => 'Store_Admin_Autocomplete',
						'class' => WitchcraftTool::class,
						'req_params' => ['locale' => 'parent.children[language]'],
						'page_limit' => 10,
						'primary_key' => 'id',
						'allow_clear' => true,
						'delay' => 250,
						'language' => $language,
						'constraints' => [new NotBlank()]
					]);
				break;
			default:
				$categories = [
						$this->translator->trans("store.index.".ucfirst(Store::CLOTH_CATEGORY), [], 'validators') => Store::CLOTH_CATEGORY,
						$this->translator->trans("store.index.".ucfirst(Store::FUNNY_CATEGORY), [], 'validators') => Store::FUNNY_CATEGORY,
						$this->translator->trans("store.index.".ucfirst(Store::GOTHIC_CLOTH_CATEGORY), [], 'validators') => Store::GOTHIC_CLOTH_CATEGORY,
						$this->translator->trans("store.index.".ucfirst(Store::MUG_CATEGORY), [], 'validators') => Store::MUG_CATEGORY,
						$this->translator->trans("store.index.".ucfirst(Store::STICKER_CATEGORY), [], 'validators') => Store::STICKER_CATEGORY
				];

				ksort($categories);

				$builder->add('category', ChoiceType::class, ['choices' => $categories, 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators']);
		}

        $builder
            ->add('title', TextType::class, ['required' =>true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => false])
            ->add('imageEmbeddedCode', TextareaType::class, ['required' => true])
			->add('url', UrlType::class)
			->add('amazonCode', TextType::class, ['required' => true])
			->add('currencyPrice', ChoiceType::class, ['choices' => Currency::getSymboleValues(), 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'])
			->add('price', NumberType::class, ['required' => true, 'translation_domain' => 'validators', "required" => false])
			->add('platform', ChoiceType::class, ['choices' => [ucfirst(Store::ALIEXPRESS_PLATFORM) => Store::ALIEXPRESS_PLATFORM, ucfirst(Store::AMAZON_PLATFORM) => Store::AMAZON_PLATFORM, ucfirst(Store::SPREADSHOP_PLATFORM) => Store::SPREADSHOP_PLATFORM, ucfirst(Store::TEMU_PLATFORM) => Store::TEMU_PLATFORM], 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators'])
		;
		
		if(!empty($fields))
			$builder->add('characteristic', StoreEditType::class, ['required' => false, "fields" => $fields]);

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($options)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();

			if(empty($form->get("photo_selector")->getData()) and empty($form->get("photo")->getData()) and empty($form->get("imageEmbeddedCode")->getData()))
				if($data->getPlatform() != Store::AMAZON_PLATFORM)
					$form->get('imageEmbeddedCode')->addError(new FormError($notBlank->message));
			
			if($data->getPlatform() == Store::AMAZON_PLATFORM and empty($data->getAmazonCode()))
				$form->get('amazonCode')->addError(new FormError($notBlank->message));
			
			if(in_array($data->getPlatform(), [Store::ALIEXPRESS_PLATFORM, Store::TEMU_PLATFORM]) and empty($data->getUrl()))
				$form->get('url')->addError(new FormError($notBlank->message));

			if((!empty($data->getPrice()) and empty($data->getCurrencyPrice())) or (empty($data->getPrice()) and !empty($data->getCurrencyPrice())))
			{
				if(empty($data->getCurrencyPrice()))
					$form->get('price')->addError(new FormError($notBlank->message));
				if(empty($data->getPrice()))
					$form->get('price')->addError(new FormError($notBlank->message));
			}
		});
		
		$builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){
			$data = $event->getData();

			if(!empty($url = $data["imageEmbeddedCode"])) {
				if(filter_var($url, FILTER_VALIDATE_URL) !== false) {
					$data["imageEmbeddedCode"] = '<img src="'.$url.'" style="width: 100%" />';
					$event->setData($data);
				}
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_store_storeadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Store',
			'locale' => 'fr'
		]);
	}
}