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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\Field\DatePartialType;
use App\Form\Field\SourceEditType;
use App\Form\Type\FileSelectorType;
use App\Form\EventListener\InternationalNameFieldListener;

use App\Entity\Language;

class WebDirectoryAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('link', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
			->add('illustration', IllustrationType::class, ['required' => true, 'base_path' => 'WebDirectory_Admin_ShowImageSelectorColorbox'])
            ->add('language', EntityType::class, [
				'class'=> Language::class,
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
			])
			->add('websiteLanguage', EntityType::class, [
				'class'=> Language::class,
				'choice_label'=>'title',
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
			])
			->add('licence', EntityType::class, ['class'=>'App\Entity\Licence',
				'choice_label'=>'title',
				'required' => false,
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			])
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'required' => true,
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
			->add('socialNetwork', HiddenType::class, ['label' => false, 'required' => false, 'attr' => ['class' => 'invisible']])
			->add('text', TextareaType::class, ['required' => false])
			->add('foundedYear', DatePartialType::class, ['required' => false])
			->add('defunctYear', DatePartialType::class, ['required' => false])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, ['required' => false])
		;

		$socialNetworkFacebookDefault = "";
		$socialNetworkTwitterDefault = "";
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
			->add('socialNetworkFacebook', TextType::class, ['label' => 'Facebook', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Facebook', 'class' => 'social_network_select'], 'data' => $socialNetworkFacebookDefault, 'constraints' => [new Url()]])
			->add('socialNetworkTwitter', TextType::class, ['label' => 'Twitter', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Twitter', 'class' => 'social_network_select'], 'data' => $socialNetworkTwitterDefault, 'constraints' => [new Url()]])
			->add('socialNetworkRSS', TextType::class, ['label' => 'RSS', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'RSS', 'class' => 'social_network_select'], 'data' => $socialNetworkRSSDefault, 'constraints' => [new Url()]])
			->add('socialNetworkYoutube', TextType::class, ['label' => 'Youtube', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Youtube', 'class' => 'social_network_select'], 'data' => $socialNetworkYoutubeDefault, 'constraints' => [new Url()]])
			->add('socialNetworkInstagram', TextType::class, ['label' => 'Instagram', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Instagram', 'class' => 'social_network_select'], 'data' => $socialNetworkInstagramDefault, 'constraints' => [new Url()]])
			->add('socialNetworkPinterest', TextType::class, ['label' => 'Pinterest', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Pinterest', 'class' => 'social_network_select'], 'data' => $socialNetworkPinterestDefault, 'constraints' => [new Url()]])
			->add('socialNetworkDelicious', TextType::class, ['label' => 'Delicious', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Delicious', 'class' => 'social_network_select'], 'data' => $socialNetworkDeliciousDefault, 'constraints' => [new Url()]])
			->add('socialNetworkGithub', TextType::class, ['label' => 'Github', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Github', 'class' => 'social_network_select'], 'data' => $socialNetworkGithubDefault, 'constraints' => [new Url()]])
			->add('socialNetworkLinkedin', TextType::class, ['label' => 'Linkedin', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Linkedin', 'class' => 'social_network_select'], 'data' => $socialNetworkLinkedinDefault, 'constraints' => [new Url()]])
			->add('socialNetworkTumblr', TextType::class, ['label' => 'Tumblr', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Tumblr', 'class' => 'social_network_select'], 'data' => $socialNetworkTumblrDefault, 'constraints' => [new Url()]])
			->add('socialNetworkVimeo', TextType::class, ['label' => 'Vimeo', 'required' => false, 'mapped' => false, 'attr' => ['data-name' => 'Vimeo', 'class' => 'social_network_select'], 'data' => $socialNetworkVimeoDefault, 'constraints' => [new Url()]])
			->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmitData'])
		;

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

	public function onPreSubmitData(FormEvent $event)
	{
		$data = $event->getData();

		$socialNetworkJson = [
			["socialNetwork" => "Facebook", "url" => $data['socialNetworkFacebook']],
			["socialNetwork" => "Twitter", "url" => $data['socialNetworkTwitter']],
			["socialNetwork" => "RSS", "url" => $data['socialNetworkRSS']],
			["socialNetwork" => "Youtube", "url" => $data['socialNetworkYoutube']],
			["socialNetwork" => "Instagram", "url" => $data['socialNetworkInstagram']],
			["socialNetwork" => "Pinterest", "url" => $data['socialNetworkPinterest']],
			["socialNetwork" => "Delicious", "url" => $data['socialNetworkDelicious']],
			["socialNetwork" => "Github", "url" => $data['socialNetworkGithub']],
			["socialNetwork" => "Linkedin", "url" => $data['socialNetworkLinkedin']],
			["socialNetwork" => "Tumblr", "url" => $data['socialNetworkTumblr']],
			["socialNetwork" => "Vimeo", "url" => $data['socialNetworkVimeo']]
		];

		$data["socialNetwork"] = json_encode($socialNetworkJson);

		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_webdirectory_webdirectoryadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\WebDirectory',
			'locale' => 'fr'
		]);
	}
}