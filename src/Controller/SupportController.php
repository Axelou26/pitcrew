<?php

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Form\SupportTicketType;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/support')]
class SupportController extends AbstractController
{
    #[Route('/', name: 'app_support_index')]
    public function index(SupportTicketRepository $supportTicketRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur a accès au support prioritaire
        $hasPrioritySupport = $this->isGranted('PRIORITY_SUPPORT');

        // Récupérer les tickets de l'utilisateur
        $userTickets = $supportTicketRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('support/index.html.twig', [
            'tickets' => $userTickets,
            'hasPrioritySupport' => $hasPrioritySupport,
        ]);
    }

    #[Route('/new', name: 'app_support_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur a accès au support prioritaire
        $hasPrioritySupport = $this->isGranted('PRIORITY_SUPPORT');

        $ticket = new SupportTicket();
        $ticket->setUser($user);
        $ticket->setStatus('new');
        $ticket->setPriority($hasPrioritySupport ? 'high' : 'normal');

        $form = $this->createForm(SupportTicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticket);
            $entityManager->flush();

            // Message différent selon le niveau de priorité
            if ($hasPrioritySupport) {
                $this
                    ->addFlash('success', 'Votre demande a été soumise et sera traitée en priorité par notre équipe
                        .');
            } else {
                $this
                    ->addFlash('success', 'Votre demande a été soumise
                        . Notre équipe y répondra dans les meilleurs délais.');
            }

            return $this->redirectToRoute('app_support_index');
        }

        return $this->render('support/new.html.twig', [
            'form' => $form->createView(),
            'hasPrioritySupport' => $hasPrioritySupport,
        ]);
    }

    #[Route('/{id}', name: 'app_support_show', methods: ['GET'])]
    public function show(SupportTicket $ticket): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire du ticket
        if ($ticket->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce ticket.');
        }

        // Vérifier si l'utilisateur a accès au support prioritaire
        $hasPrioritySupport = $this->isGranted('PRIORITY_SUPPORT');

        return $this->render('support/show.html.twig', [
            'ticket' => $ticket,
            'hasPrioritySupport' => $hasPrioritySupport,
        ]);
    }

    #[Route('/{id}/reply', name: 'app_support_reply', methods: ['POST'])]
    public function reply(Request $request, SupportTicket $ticket, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le propriétaire du ticket
        if ($ticket->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce ticket.');
        }

        $content = $request->request->get('reply');

        if (!$content) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('app_support_show', ['id' => $ticket->getId()]);
        }

        // Ajouter la réponse au ticket
        $ticket->addReply([
            'user' => $this->getUser()->getId(),
            'content' => $content,
            'created_at' => new \DateTime(),
        ]);

        // Mettre à jour le statut du ticket
        $ticket->setStatus('waiting_for_support');
        $ticket->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Votre réponse a été ajoutée.');
        return $this->redirectToRoute('app_support_show', ['id' => $ticket->getId()]);
    }
}
