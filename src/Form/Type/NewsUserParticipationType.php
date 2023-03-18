<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class NewsUserParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["language"];
		$user = $options["user"];
		$securityUser = $options["securityUser"];

        $builder
            ->add('title', TextType::class, array('label' => 'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
            // ->add('abstractText', TextareaType::class, array('required' => false, 'attr' => array('class' => 'edit')))
            ->add('text', TextareaType::class, array('label' => 'Témoignage', 'required' => true, 'constraints' => array(new NotBlank())))
            /*->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
				'choice_label'=>'title',
				'required' => false,
				'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}
			))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
				'choice_label'=>'title', 
				'required' => true,
				'placeholder' => "",
				'constraints' => array(new NotBlank()),
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			))*/
			->add('validate', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn')
			))
			->add('illustration', FileType::class, array('data_class' => null, 'required' => false))
			;
			
		if(!empty($securityUser) and $securityUser->isGranted('IS_AUTHENTICATED_FULLY'))
		{
			$builder
			->add('preview', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
			))
			->add('draft', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
				'validation_groups' => false
			));
		}

		if(!is_object($user))
		{
			$builder
				->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
				->add('emailAuthor', 'text', array('constraints' => array(new NotBlank(), new Email())))
				;
		}
		else
		{
			$builder->add('isAnonymous', ChoiceType::class, array(
				'choices'   => array(
					'news.new.PublishedAnonymously' => 1,
					'news.new.PostedWithMyUserAccount' => 0,
				),
				'multiple'  => false,
				'expanded'  => false,
				'constraints' => array(new NotBlank()),
				'placeholder' => false,
				'data' => 0,
				'translation_domain' => 'validators'
			));
		}

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event)
		{
			$data = $event->getData();
			$form = $event->getForm();

			if(is_object($data->getIllustration()))
			{
				$formatArray = array('image/png', 'image/jpeg', 'image/gif');

				if(!in_array($data->getIllustration()->getClientMimeType(), $formatArray))
					$form->get('illustration')->addError(new FormError('news.error.FileFormat'));

				if($data->getIllustration()->getSize() > $data->getIllustration()->getMaxFilesize())
					$form->get('illustration')->addError(new FormError('news.error.FileSizeError'));
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_news_newsuserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\News',
			'translation_domain' => 'validators',
			'language' => 'fr',
			'user' => null,
			'securityUser' => null
		));
	}
}