<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class InternationalizationAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$locales = $options["locales"];

        $builder
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er) use ($locales)
					{
						$qb = $er->createQueryBuilder('u')
								  ->orderBy('u.title', 'ASC');

						if(!empty($locales))
							$qb->where("u.abbreviation NOT IN (:locales)")
							   ->setParameter("locales", $locales);
						
						return $qb;
					},
				))
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_admin_internationalizationadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			"locales" => []
		]);
	}
}