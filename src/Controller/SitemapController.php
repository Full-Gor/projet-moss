<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap')]
    public function index(): Response
    {
        $urls = [
            ['loc' => $this->generateUrl('app_home'), 'priority' => '1.0'],
            ['loc' => $this->generateUrl('app_produit'), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_histoire'), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_about'), 'priority' => '0.6'],
        ];

        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $url['loc'] . '</loc>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        $response->setContent($xml);
        return $response;
    }
}
