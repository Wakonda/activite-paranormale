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
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
					'choice_label'=>'title', 
					'required' => true,
					'query_builder' => function(EntityRepository $er) 
					{
						return $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');
					},
					'constraints' => array(new NotBlank())
					));

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
				$builder->add('category', ChoiceType::class, ['choices' => ["store.admin.".ucfirst(Store::CLOTH_CATEGORY) => Store::CLOTH_CATEGORY, "store.admin.".ucfirst(Store::FUNNY_CATEGORY) => Store::FUNNY_CATEGORY], 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators']);
		}

        $builder
            ->add('title', TextType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => false))
            ->add('imageEmbeddedCode', TextareaType::class, array('required' =>true, 'constraints' => array(new NotBlank())))
			->add('url', UrlType::class)
			->add('amazonCode', TextType::class, array('required' => true))
			->add('currencyPrice', ChoiceType::class, array('choices' => Currency::getSymboleValues(), 'expanded' => false, 'multiple' => false, 'required' => false, 'translation_domain' => 'validators'))
			->add('price', NumberType::class, array('required' => true, 'translation_domain' => 'validators', "required" => false))
			->add('platform', ChoiceType::class, array('choices' => [ucfirst(Store::ALIEXPRESS_PLATFORM) => Store::ALIEXPRESS_PLATFORM, ucfirst(Store::AMAZON_PLATFORM) => Store::AMAZON_PLATFORM], 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => array(new NotBlank()), 'translation_domain' => 'validators'))
		;
		
		if(!empty($fields))
			$builder->add('characteristic', StoreEditType::class, array('required' => false, "fields" => $fields));

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event)
		{
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();
			
			if($data->getPlatform() == Store::AMAZON_PLATFORM and empty($data->getAmazonCode()))
				$form->get('amazonCode')->addError(new FormError($notBlank->message));
			
			if($data->getPlatform() == Store::ALIEXPRESS_PLATFORM and empty($data->getUrl()))
				$form->get('url')->addError(new FormError($notBlank->message));

			if((!empty($data->getPrice()) and empty($data->getCurrencyPrice())) or (empty($data->getPrice()) and !empty($data->getCurrencyPrice())))
			{
				if(empty($data->getCurrencyPrice()))
					$form->get('price')->addError(new FormError($notBlank->message));
				if(empty($data->getPrice()))
					$form->get('price')->addError(new FormError($notBlank->message));
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_store_storeadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Store',
			'locale' => 'fr'
		));
	}
}