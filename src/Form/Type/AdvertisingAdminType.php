<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityRepository;

use App\Entity\Language;
use App\Entity\Advertising;

class AdvertisingAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$locale = $options["locale"];

        $builder
            ->add('title', TextType::class, array(
                'constraints' => new NotBlank(), "label" => "admin.advertising.Title"
            ))
			->add('text', TextareaType::class, array(
                'required' => false, "label" => "admin.advertising.Text", 'attr' => array('class' => 'redactor')
            ))
			->add('width', IntegerType::class, array("label" => "admin.advertising.Width", "required" => true, "constraints" => new NotBlank()))
			->add('height', IntegerType::class, array("label" => "admin.advertising.Height", "required" => true, "constraints" => new NotBlank()))
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
			));
    }

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			"data_class" => Advertising::class,
			"locale" => null
		));
	}
	
    public function getBlockPrefix()
    {
        return 'ap_advertising_advertisingadmintype';
    }
}