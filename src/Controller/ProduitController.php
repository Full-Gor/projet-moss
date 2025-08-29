<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;

class ProduitController extends AbstractController
{
    #[Route('/produit', name: 'app_produit')]
    public function index(): Response
    {
        return $this->render('produit/index.html.twig', [
            'prix' => 149
        ]);
    }

    #[Route('/ajouter-panier', name: 'app_add_cart', methods: ['POST'])]
    public function addToCart(Request $request, EntityManagerInterface $em): Response
    {
        $commande = new Commande();
        $commande->setCouleur($request->request->get('couleur'));
        $commande->setQuantite(1);
        $commande->setPrix(149);
        $commande->setCreatedAt(new \DateTimeImmutable());
        
        $em->persist($commande);
        $em->flush();
        
        $this->addFlash('success', 'Produit ajouté au panier');
        return $this->redirectToRoute('app_produit');
    }
}
