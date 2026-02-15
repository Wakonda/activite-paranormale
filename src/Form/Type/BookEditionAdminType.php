<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Isbn;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Entity\Book;
use App\Entity\BookEditionBiography;
use App\Form\Field\DatePartialType;

class BookEditionAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];

        $builder
            ->add('subtitle', TextType::class, array('required' => false))
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => null, 'file_path' => $builder->getData()->getAssetImagePath()))
            ->add('backCover', TextareaType::class, array('required' => false))
			->add('isbn10', TextType::class, array('required' => false, 'constraints' => array(new Isbn("isbn10"))))
			->add('isbn13', TextType::class, array('required' => false, 'constraints' => array(new Isbn("isbn13"))))
			->add('numberPage', IntegerType::class, array('required' => true, 'constraints' => array(new NotBlank())))
			->add('publicationDate', DatePartialType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('wholeBook', FileType::class, array('data_class' => null, 'required' => false))
            ->add('format', ChoiceType::class, array('choices' => array('bookEdition.admin.Paperback' => 'paperback', 'bookEdition.admin.Hardcover' => 'hardcover', 'bookEdition.admin.Audiobook' => 'audiobook', 'bookEdition.admin.Ebook' => 'ebook'), 'expanded' => false, 'multiple' => false, 'required' =>true, 'constraints' => array(new NotBlank()), 'translation_domain' => 'validators'))
			->add('publisher', EntityType::class, array('class'=>'App\Entity\Publisher',
				'choice_label'=>'title',
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(\App\Repository\PublisherRepository $repository) use ($language) { return $repository->createQueryBuilder("p")->join("p.language", "l")->where("l.abbreviation = :language")->setParameter("language", $language)->orderBy("p.title", "ASC");}))
			->add('biographies', CollectionType::class, array("label" => false, "required" => false, 'entry_type' => BiographiesAdminType::class, "allow_add" => true, "allow_delete" => true, "entry_options" => ["label" => false, "data_class" => BookEditionBiography::class, "role_field" => false, "language" => $language, "query_parameters" => ["locale" => $language]]))
			->add('file_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getWholeBook()])
		;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_book_bookextensionadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\BookEdition',
			'locale' => 'fr'
		));
	}
}