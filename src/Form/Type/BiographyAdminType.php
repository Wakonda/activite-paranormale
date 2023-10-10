<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

use App\Form\Field\SourceEditType;
use App\Form\Field\DatePartialType;
use App\Form\EventListener\InternationalNameFieldListener;
use App\Entity\Biography;
use App\Entity\EntityLinkBiography;
use App\Form\Field\IdentifiersEditType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use App\Form\DataTransformer\OccupationBiographyTransformer;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Contracts\Translation\TranslatorInterface;

class BiographyAdminType extends AbstractType
{
    public function __construct(
        private OccupationBiographyTransformer $occupationBiographyTransformer,
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$action = $options['action'];
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('text', TextareaType::class, array('required' => false))
            ->add('source', SourceEditType::class, array('required' => false))
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
			->add('kind', ChoiceType::class, ['multiple' => false, 'expanded' => false,
					"choices" => [
						"biography.form.Person" => Biography::PERSON,
						"biography.form.FictionalCharacter" => Biography::FICTIONAL_CHARACTER,
						"biography.form.Other" => Biography::OTHER
					],
					'translation_domain' => 'validators'
			])
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'Biography_Admin_ShowImageSelectorColorbox'))
			->add('birthDate', DatePartialType::class, ['required' => false])
			->add('deathDate', DatePartialType::class, ['required' => false])
			->add('nationality', EntityType::class, array('class'=>'App\Entity\Region',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) { return $repository->getCountryByLanguage($language);}))
			->add('wikidata', TextType::class, ['required' => false])
			->add('links', HiddenType::class, array('label' => false, 'required' => false, 'attr' => ['class' => 'invisible']))
			->add('identifiers', IdentifiersEditType::class, ['required' => false, 'enum' => \App\Service\Identifier::getBiographyIdentifiers()]);

		$entities = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		$occupationChoice = [];

		foreach($entities as $entity) {
			if(in_array(\App\Entity\Interfaces\BiographyInterface::class, class_implements($entity))) {
				foreach($entity::getOccupations() as $occupation)
					$occupationChoice[$this->translator->trans("biography.search.".(new \ReflectionClass($entity))->getShortName(), [], 'validators')][$occupation] = $this->translator->trans("biography.occupation.".ucfirst($occupation), [], 'validators');

				asort($occupationChoice[$this->translator->trans("biography.search.".(new \ReflectionClass($entity))->getShortName(), [], 'validators')]);
			}
		}

		$occupationByCanonicalName = array_flip(array_reduce($occupationChoice, 'array_merge', []));
		$occupationChoiceDatas = [];
		
		foreach(array_merge(...array_values($occupationChoice)) as $search)
		{
			$found = array_filter($occupationChoice,function($v, $k) use ($search) {
				return in_array($search, $v);
			}, ARRAY_FILTER_USE_BOTH); 

			$occupationChoiceDatas[implode(" / ", array_keys($found))][$search] = $occupationByCanonicalName[$search];
		}

		$occupationArray = [];
		$occupationAttributeArray = [];

		$roles = $this->entityManager->getRepository(\App\Entity\EntityLinkBiography::class)->findBy(["biography"=> $builder->getData()]);
		
		foreach($roles as $role)
			if(get_class($roles[0]) != \App\Entity\EntityLinkBiography::class)
				$occupationAttributeArray[$this->translator->trans("biography.occupation.".ucfirst($role->getOccupation()), [], 'validators')] = ["data-readonly" => 1];

		ksort($occupationChoiceDatas);

		$builder->add('occupations', ChoiceType::class, ['required' => false, 'choices' => $occupationChoiceDatas, 'translation_domain' => 'validators', "multiple" => true, "mapped" => false, 'choice_attr' =>  $occupationAttributeArray]);

		$this->occupationBiographyTransformer->biography = $builder->getData();
		$builder->get('occupations')->addModelTransformer($this->occupationBiographyTransformer);

		$socialNetworkWebsiteDefault = "";
		$socialNetworkFacebookDefault = "";
		$socialNetworkTwitterDefault = "";
		$socialNetworkYoutubeDefault = "";
		$socialNetworkInstagramDefault = "";
		$socialNetworkPinterestDefault = "";
		$socialNetworkLinkedinDefault = "";

		if($builder->getData()->getLinks() != null)
		{
			$socialNetworkArray = json_decode($builder->getData()->getLinks());

			foreach($socialNetworkArray as $socialNetwork)
			{
				switch($socialNetwork->link)
				{
					case "Link":
						$socialNetworkWebsiteDefault = $socialNetwork->url;
						break;
					case "Facebook":
						$socialNetworkFacebookDefault = $socialNetwork->url;
						break;
					case "Twitter":
						$socialNetworkTwitterDefault = $socialNetwork->url;
						break;
					case "Youtube":
						$socialNetworkYoutubeDefault = $socialNetwork->url;
						break;
					case "Instagram":
						$socialNetworkInstagramDefault = $socialNetwork->url;
						break;
					case "Pinterest":
						$socialNetworkPinterestDefault = $socialNetwork->url;
						break;
					case "Linkedin":
						$socialNetworkLinkedinDefault = $socialNetwork->url;
						break;
				}
			}
		}

		$builder
			->add('socialNetworkFacebook', TextType::class, array('label' => 'Facebook', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Facebook', 'class' => 'social_network_select'), 'data' => $socialNetworkFacebookDefault, 'constraints' => array(new Url())))
			->add('socialNetworkTwitter', TextType::class, array('label' => 'Twitter', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Twitter', 'class' => 'social_network_select'), 'data' => $socialNetworkTwitterDefault, 'constraints' => array(new Url())))
			->add('socialNetworkWebsite', TextType::class, array('label' => 'biography.admin.Link', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Link', 'class' => 'social_network_select'), 'data' => $socialNetworkWebsiteDefault, 'constraints' => array(new Url())))
			->add('socialNetworkYoutube', TextType::class, array('label' => 'Youtube', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Youtube', 'class' => 'social_network_select'), 'data' => $socialNetworkYoutubeDefault, 'constraints' => array(new Url())))
			->add('socialNetworkInstagram', TextType::class, array('label' => 'Instagram', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Instagram', 'class' => 'social_network_select'), 'data' => $socialNetworkInstagramDefault, 'constraints' => array(new Url())))
			->add('socialNetworkPinterest', TextType::class, array('label' => 'Pinterest', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Pinterest', 'class' => 'social_network_select'), 'data' => $socialNetworkPinterestDefault, 'constraints' => array(new Url())))
			->add('socialNetworkLinkedin', TextType::class, array('label' => 'Linkedin', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Linkedin', 'class' => 'social_network_select'), 'data' => $socialNetworkLinkedinDefault, 'constraints' => array(new Url())))

			->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitData'))
		;

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }
	
	public function onPreSubmitData(FormEvent $event)
	{
		$data = $event->getData();

		$linkJson = [
			["link" => "Facebook", "url" => $data['socialNetworkFacebook'], "label" => "FacebookAccount"],
			["link" => "Twitter", "url" => $data['socialNetworkTwitter'], "label" => "TwitterAccount"],
			["link" => "Link", "url" => $data['socialNetworkWebsite'], "label" => "Website"],
			["link" => "Youtube", "url" => $data['socialNetworkYoutube'], "label" => "YoutubeAccount"],
			["link" => "Instagram", "url" => $data['socialNetworkInstagram'], "label" => "InstagramAccount"],
			["link" => "Pinterest", "url" => $data['socialNetworkPinterest'], "label" => "PinterestAccount"],
			["link" => "Linkedin", "url" => $data['socialNetworkLinkedin'], "label" => "LinkedInAccount"]
		];

		$data["links"] = json_encode($linkJson);
		
		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_quotation_biographyadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Biography',
			'action' => null,
			'locale' => 'fr'
		]);
	}
}