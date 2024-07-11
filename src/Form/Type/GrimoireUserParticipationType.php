<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Symfony\Component\Validator\Constraints\NotBlank;

class GrimoireUserParticipationType extends AbstractType
{
	public function __construct(private TokenStorageInterface $token){}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["language"];
		$user = $this->token->getToken()->getUser();

        $builder
            ->add('title', TextType::class, array('label' => 'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label' => 'Texte', 'required' => true, 'constraints' => array(new NotBlank())))
            /*->add('surTheme', EntityType::class, array('class'=>'App\Entity\SurThemeGrimoire', 
					'choice_label'=>'title', 
					'required' => true,
					'group_by' => 'getMenuGrimoireTitle',
					'constraints' => array(new NotBlank()),
					'query_builder' => function(EntityRepository $er) use ($language) {
						return $er->createQueryBuilder('u')
								->innerJoin('u.language', 'l')
								->innerJoin('u.parentTheme', 'mg')
								->where('l.abbreviation = :language')
								->setParameter('language', $language)
								->orderBy('u.title', 'ASC');
					}
			))*/
			->add('validate', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn')
			));

		if(!is_object($user))
			$builder->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())));
		else {
			$builder->add('isAnonymous', ChoiceType::class, array(
				'choices'   => array(
					'witchcraft.new.PublishedAnonymously' => 1,
					'witchcraft.new.PostedWithMyUserAccount' => 0,
				),
				'multiple'  => false,
				'expanded'  => false,
				'constraints' => array(new NotBlank()),
				'placeholder' => false,
				'data' => 0,
				'translation_domain' => 'validators'
			));
		}
    }

    public function getBlockPrefix()
    {
        return 'ap_witchcraft_grimoireuserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Grimoire',
			'translation_domain' => 'validators',
			'language' => 'fr'
		]);
	}
}