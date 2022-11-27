<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\Field\DatePartialType;
use App\Form\Field\SourceEditType;
use App\Form\Type\FileSelectorType;
use App\Form\EventListener\InternationalNameFieldListener;

class WebDirectoryAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('link', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('logo', FileType::class, array('data_class' => null, 'required' => true))
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => 'WebDirectory_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getLogo()])
            ->add('language', EntityType::class, array(
					'class'=>'App\Entity\Language', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) 
						{
							return $er->createQueryBuilder('u')
									->orderBy('u.title', 'ASC');
						},
					))
			->add('websiteLanguage', EntityType::class, array(
					'class'=>'App\Entity\Language', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) 
						{
							return $er->createQueryBuilder('u')
									->orderBy('u.title', 'ASC');
						},
					))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => false,
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			))
			->add('socialNetwork', HiddenType::class, array('label' => false, 'required' => false, 'attr' => array('class' => 'invisible')))
			->add('text', TextareaType::class, array('required' => false))
			->add('foundedYear', DatePartialType::class, ['required' => false])
			->add('defunctYear', DatePartialType::class, ['required' => false])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, array('required' => false))
		;

		$socialNetworkFacebookDefault = "";
		$socialNetworkTwitterDefault = "";
		$socialNetworkGooglePlusDefault = "";
		$socialNetworkRSSDefault = "";
		$socialNetworkYoutubeDefault = "";
		$socialNetworkInstagramDefault = "";
		$socialNetworkPinterestDefault = "";
		$socialNetworkDeliciousDefault = "";
		$socialNetworkGithubDefault = "";
		$socialNetworkLinkedinDefault = "";
		$socialNetworkTumblrDefault = "";
		$socialNetworkVimeoDefault = "";

		if($builder->getData() != null)
		{
			if($builder->getData()->getSocialNetwork() != null)
			{
				$socialNetworkArray = json_decode($builder->getData()->getSocialNetwork());
				
				foreach($socialNetworkArray as $socialNetwork)
				{
					switch($socialNetwork->socialNetwork)
					{
						case "Facebook":
							$socialNetworkFacebookDefault = $socialNetwork->url;
							break;
						case "Twitter":
							$socialNetworkTwitterDefault = $socialNetwork->url;
							break;
						case "GooglePlus":
							$socialNetworkGooglePlusDefault = $socialNetwork->url;
							break;
						case "RSS":
							$socialNetworkRSSDefault = $socialNetwork->url;
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
						case "Delicious":
							$socialNetworkDeliciousDefault = $socialNetwork->url;
							break;
						case "Github":
							$socialNetworkGithubDefault = $socialNetwork->url;
							break;
						case "Linkedin":
							$socialNetworkLinkedinDefault = $socialNetwork->url;
							break;
						case "Tumblr":
							$socialNetworkTumblrDefault = $socialNetwork->url;
							break;
						case "Vimeo":
							$socialNetworkVimeoDefault = $socialNetwork->url;
							break;
					}
				}
			}
		}

		$builder
			->add('socialNetworkFacebook', TextType::class, array('label' => 'Facebook', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Facebook', 'class' => 'social_network_select'), 'data' => $socialNetworkFacebookDefault, 'constraints' => array(new Url())))
			->add('socialNetworkTwitter', TextType::class, array('label' => 'Twitter', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Twitter', 'class' => 'social_network_select'), 'data' => $socialNetworkTwitterDefault, 'constraints' => array(new Url())))
			->add('socialNetworkGooglePlus', TextType::class, array('label' => 'GooglePlus', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'GooglePlus', 'class' => 'social_network_select'), 'data' => $socialNetworkGooglePlusDefault, 'constraints' => array(new Url())))
			->add('socialNetworkRSS', TextType::class, array('label' => 'RSS', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'RSS', 'class' => 'social_network_select'), 'data' => $socialNetworkRSSDefault, 'constraints' => array(new Url())))
			->add('socialNetworkYoutube', TextType::class, array('label' => 'Youtube', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Youtube', 'class' => 'social_network_select'), 'data' => $socialNetworkYoutubeDefault, 'constraints' => array(new Url())))
			->add('socialNetworkInstagram', TextType::class, array('label' => 'Instagram', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Instagram', 'class' => 'social_network_select'), 'data' => $socialNetworkInstagramDefault, 'constraints' => array(new Url())))
			->add('socialNetworkPinterest', TextType::class, array('label' => 'Pinterest', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Pinterest', 'class' => 'social_network_select'), 'data' => $socialNetworkPinterestDefault, 'constraints' => array(new Url())))
			->add('socialNetworkDelicious', TextType::class, array('label' => 'Delicious', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Delicious', 'class' => 'social_network_select'), 'data' => $socialNetworkDeliciousDefault, 'constraints' => array(new Url())))
			->add('socialNetworkGithub', TextType::class, array('label' => 'Github', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Github', 'class' => 'social_network_select'), 'data' => $socialNetworkGithubDefault, 'constraints' => array(new Url())))
			->add('socialNetworkLinkedin', TextType::class, array('label' => 'Linkedin', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Linkedin', 'class' => 'social_network_select'), 'data' => $socialNetworkLinkedinDefault, 'constraints' => array(new Url())))
			->add('socialNetworkTumblr', TextType::class, array('label' => 'Tumblr', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Tumblr', 'class' => 'social_network_select'), 'data' => $socialNetworkTumblrDefault, 'constraints' => array(new Url())))
			->add('socialNetworkVimeo', TextType::class, array('label' => 'Vimeo', 'required' => false, 'mapped' => false, 'attr' => array('data-name' => 'Vimeo', 'class' => 'social_network_select'), 'data' => $socialNetworkVimeoDefault, 'constraints' => array(new Url())))
		
			->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitData'))
		;
		
		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }
	
	public function onPreSubmitData(FormEvent $event)
	{
		$data = $event->getData();

		$socialNetworkJson = array(
			array("socialNetwork" => "Facebook", "url" => $data['socialNetworkFacebook']),
			array("socialNetwork" => "Twitter", "url" => $data['socialNetworkTwitter']),
			array("socialNetwork" => "GooglePlus", "url" => $data['socialNetworkGooglePlus']),
			array("socialNetwork" => "RSS", "url" => $data['socialNetworkRSS']),
			array("socialNetwork" => "Youtube", "url" => $data['socialNetworkYoutube']),
			array("socialNetwork" => "Instagram", "url" => $data['socialNetworkInstagram']),
			array("socialNetwork" => "Pinterest", "url" => $data['socialNetworkPinterest']),
			array("socialNetwork" => "Delicious", "url" => $data['socialNetworkDelicious']),
			array("socialNetwork" => "Github", "url" => $data['socialNetworkGithub']),
			array("socialNetwork" => "Linkedin", "url" => $data['socialNetworkLinkedin']),
			array("socialNetwork" => "Tumblr", "url" => $data['socialNetworkTumblr']),
			array("socialNetwork" => "Vimeo", "url" => $data['socialNetworkVimeo'])
		);
		
		$data["socialNetwork"] = json_encode($socialNetworkJson);
		
		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_webdirectory_webdirectoryadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\WebDirectory',
			'locale' => 'fr'
		));
	}
}