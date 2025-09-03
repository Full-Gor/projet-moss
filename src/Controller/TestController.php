<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TestLog;
use App\Repository\TestLogRepository;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(TestLogRepository $testLogRepository): Response
    {
        try {
            $logs = $testLogRepository->findRecentLogs(10);
            $status = 'success';
            $message = 'Connexion à la base de données réussie !';
        } catch (\Exception $e) {
            $logs = [];
            $status = 'error';
            $message = 'Erreur de connexion : ' . $e->getMessage();
        }

        return $this->render('test/index.html.twig', [
            'status' => $status,
            'message' => $message,
            'logs' => $logs,
            'timestamp' => new \DateTime()
        ]);
    }

    #[Route('/test/ajouter', name: 'app_test_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $log = new TestLog();
            $log->setMessage('Test de base de données - ' . date('H:i:s'));
            $log->setType('test');

            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Log ajouté avec succès ! ID: ' . $log->getId());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_test');
    }
}
