<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactPrivateMessageType extends AbstractType
{
private $token;

public function __construct(TokenStorageInterface $token)
{
   $this->token = $token;
}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$sender = $this->token->getToken()->getUser();

		if(empty($sender)) {
			$builder
				->add('pseudoContact', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
				->add('emailContact', TextType::class, array('required' => true, 'constraints' => array(new NotBlank(), new Email())))
				->add('captcha', TextType::class, array('required' => true, 'constraints' => [new NotBlank()], 'mapped' => false));
        }
// dd($options["initialMessage"]);
		$builder
			->add('subjectContact', TextType::class, array('required' => true, 'constraints' => [new NotBlank()], "attr" => ["readonly" => !empty($options["initialMessage"])]))
            ->add('messageContact', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_contact_contactprivatemessagetype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Contact',
			"initialMessage" => null
		));
	}
}