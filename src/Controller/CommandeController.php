<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
        ]);
    }

    #[Route('/historique', name: 'app_commande_historique')]
    public function historique(CommandeRepository $commandeRepository): Response
    {
        return $this->render('commande/historique.html.twig', [
            'commandes' => $commandeRepository->findBy([], ['date_commande' => 'DESC'])
        ]);
    }

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $commande->setQuantite((int) $request->request->get('quantite'));
            $em->flush();

            $this->addFlash('success', 'Commande modifiée avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/{id}/delete', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_commande' . $commande->getId(), $request->request->get('_token'))) {
            $em->remove($commande);
            $em->flush();

            $this->addFlash('success', 'Commande supprimée avec succès !');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/delete-all', name: 'app_commande_delete_all', methods: ['POST'])]
    public function deleteAll(Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_all_commandes', $request->request->get('_token'))) {
            $commandes = $commandeRepository->findAll();

            foreach ($commandes as $commande) {
                $em->remove($commande);
            }
            $em->flush();

            $this->addFlash('success', 'Toutes les commandes ont été supprimées !');
        }

        return $this->redirectToRoute('app_profile');
    }
}
