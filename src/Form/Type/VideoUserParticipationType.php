<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class VideoUserParticipationType extends AbstractType
{
	public function __construct(private TokenStorageInterface $token){}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options["language"];
		$user = !empty($token = $this->token->getToken()) ? $token->getUser() : null;

        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'required' => false])
			->add('embeddedCode', TextareaType::class, ['required' => true, 'constraints' => [new NotBlank()], "attr" => ["placeholder" => '<iframe width="560" height="315" src="https://www.youtube.com/embed/video"></iframe>', 'rows' => 5]])
            ->add('text', TextareaType::class, array('required' => false))
			->add('validate', SubmitType::class, array(
				'attr' => array('class' => 'submitcomment btn'),
			))
			;

		if(!is_object($user)) {
			$builder
				->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())));
		} else {
			$builder->add('isAnonymous', ChoiceType::class, array(
				'choices'   => array(
					'eventMessage.new.PublishedAnonymously' => 1,
					'eventMessage.new.PostedWithMyUserAccount' => 0,
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
        return 'ap_page_videouserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Video',
			'translation_domain' => 'validators',
			'language' => 'fr'
		]);
	}
}