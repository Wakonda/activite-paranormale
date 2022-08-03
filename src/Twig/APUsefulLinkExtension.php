<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;

	use Symfony\Contracts\Translation\TranslatorInterface;

	class APUsefulLinkExtension extends AbstractExtension
	{
		private $translator;
		
		public function __construct(TranslatorInterface $translator)
		{
			$this->translator = $translator;
		}
		
		public function getFilters()
		{
			return [
				new TwigFilter('prism_formatter', [$this, 'prismFormatterFilter'])
			];
		}

		// Filters
		public function prismFormatterFilter(string $text)
		{return $text;
			$dom = new \DOMDocument();
			
			libxml_use_internal_errors(true);
			$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

			foreach($dom->getElementsByTagName('pre') as $pre) {
				$pre->setAttribute("data-prismjs-copy", $this->translator->trans('usefullink.index.Copy', [], 'validators'));
				$pre->setAttribute("data-prismjs-copy-success", $this->translator->trans('usefullink.index.Copied', [], 'validators'));
				$pre->setAttribute("data-prismjs-copy-error", $this->translator->trans('usefullink.index.Error', [], 'validators'));
			}

			return $dom->saveHTML();
		}

		public function getName()
		{
			return 'ap_usefullinkextension';
		}
	}