<?php
	namespace App\Service;

	use Symfony\Component\HttpFoundation\Response;

	use Spipu\Html2Pdf\Html2Pdf;
	use Spipu\Html2Pdf\Exception\Html2PdfException;
	use Spipu\Html2Pdf\Exception\ExceptionFormatter;

	class APHtml2Pdf
	{
		public function generatePdf($content, $params = array('title' => 'ap'))
		{
			try {
				$html2pdf = new Html2Pdf('P', 'A4', 'fr', true, 'iso-8859-1', 0);
				
				if(array_key_exists('title', $params))
					$html2pdf->pdf->SetTitle($params['title']);
				
				if(array_key_exists('author', $params))
					$html2pdf->pdf->SetAuthor($params['author']);
				
				// Remove <script> tags
				$content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
				$content = preg_replace('#<figure(.*?)>(.*?)</figure>#is', '', $content);
				$content = preg_replace('#<figcaption(.*?)>(.*?)</figcaption>#is', '', $content);
			
				$html2pdf->writeHTML($content);
				$file = $html2pdf->Output($params['title'].'.pdf');

				$response = new Response($file);
				$response->headers->set('Content-Type', 'application/pdf');

				return $response;

			} catch (Html2PdfException $e)
			{
				$formatter = new ExceptionFormatter($e);
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(500, $formatter->getHtmlMessage());
			}
		}
	}