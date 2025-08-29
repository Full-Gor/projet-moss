<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('base/index.html.twig');
    }

  // Dans PageController.php, ajoute ces routes :

#[Route('/produit', name: 'app_produit')]
public function produit(): Response
{
    return $this->render('page/produit.html.twig');
}

#[Route('/histoire', name: 'app_histoire')]
public function histoire(): Response
{
    return $this->render('page/histoire.html.twig');
}

#[Route('/panier', name: 'app_panier')]
public function panier(): Response
{
    return $this->render('page/panier.html.twig');
}

// Et créer un nouveau SecurityController pour l'authentification...

    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/cgv', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('page/cgv.html.twig');
    }
}
