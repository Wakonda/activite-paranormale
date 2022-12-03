<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class LogoAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('label'=>'Titre', 'required' =>true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label'=>'Texte', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('alt', TextType::class, array('label'=>'Alt', 'required' =>true, 'constraints' => array(new NotBlank())))
			->add('publishedAt', DateType::class, array('required' => true, 'widget' => 'single_text', 'constraints' => array(new NotBlank())))
            ->add('image', FileType::class, array("required" => true, 'data_class' => null))
			->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
							{
								return $er->createQueryBuilder('u')
										->orderBy('u.title', 'ASC');
							},
				'constraints' => array(new NotBlank())
			))
			->add('isActive', CheckboxType::class, array('required' => true))
			->add('folder', TextType::class, array('required' => true))
			->add('image_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getImage()])
        ;

		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
			$data = $event->getData();
			$form = $event->getForm();

			if($form->isValid())
			{
				$folder = $event->getData()->getFolder();
				
				if(!file_exists($data->getAssetImagePath().'/'.$folder))
					mkdir($data->getAssetImagePath().'/'.$folder, 0777);
			}
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_page_logoadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Logo',
		));
	}
}