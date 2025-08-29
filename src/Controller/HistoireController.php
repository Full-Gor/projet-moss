<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HistoireController extends AbstractController
{
    #[Route('/histoire', name: 'app_histoire')]
    public function index(): Response
    {
        return $this->render('page/histoire.html.twig');
    }
}
