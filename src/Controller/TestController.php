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
    #[Route('/test-supabase', name: 'app_test_supabase')]
    public function index(TestLogRepository $testLogRepository): Response
    {
        try {
            $logs = $testLogRepository->findRecentLogs(10);
            $status = 'success';
            $message = 'Connexion à Supabase réussie !';
        } catch (\Exception $e) {
            $logs = [];
            $status = 'error';
            $message = 'Erreur de connexion : ' . $e->getMessage();
        }

        return $this->render('test/supabase.html.twig', [
            'status' => $status,
            'message' => $message,
            'logs' => $logs,
            'timestamp' => new \DateTime()
        ]);
    }

    #[Route('/test-supabase/ajouter', name: 'app_test_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $log = new TestLog();
            $log->setMessage('Test Supabase - ' . date('H:i:s'));
            $log->setType('test');

            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Log ajouté avec succès ! ID: ' . $log->getId());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_test_supabase');
    }
}
