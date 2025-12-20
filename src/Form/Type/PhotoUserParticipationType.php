<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PhotoUserParticipationType extends AbstractType
{
	public function __construct(private TokenStorageInterface $token){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options["language"];
		$user = !empty($token = $this->token->getToken()) ? $token->getUser() : null;

        $builder
			->add('illustration', FileType::class, ['data_class' => null, 'required' => true, 'constraints' => [new NotBlank()]])
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

    public function getBlockPrefix(): string
    {
        return 'ap_page_photouserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Photo',
			'translation_domain' => 'validators',
			'language' => 'fr'
		]);
	}
}