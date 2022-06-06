<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;

class LicenceAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('logo', FileType::class, array('data_class' => null, 'required' => true))
            ->add('link', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener())
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
    }

    public function getBlockPrefix()
    {
        return 'ap_index_licenceadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Licence',
		));
	}
}