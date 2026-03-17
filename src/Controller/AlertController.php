<?php

namespace App\Controller;

use App\Entity\PostAlert;
use App\Repository\AuthorRepository;
use App\Repository\PostAlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/alerts')]
#[IsGranted('ROLE_USER')]
class AlertController extends AbstractController
{
    #[Route('', name: 'app_alert_list', methods: ['GET'])]
    public function list(PostAlertRepository $postAlertRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $alerts = $postAlertRepository->findByUser($user);

        return $this->render('alert/list.html.twig', [
            'alerts' => $alerts,
        ]);
    }

    #[Route('/subscribe/{authorId}', name: 'app_alert_subscribe', requirements: ['authorId' => '\d+'], methods: ['POST'])]
    public function subscribe(
        Request $request,
        int $authorId,
        AuthorRepository $authorRepository,
        PostAlertRepository $postAlertRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('alert_subscribe_' . $authorId, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_index')));
        }

        $author = $authorRepository->find($authorId);
        if (!$author) {
            $this->addFlash('error', 'Auteur non trouvé.');
            return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_index')));
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $existing = $postAlertRepository->findOneByUserAndAuthor($user, $author);
        if ($existing) {
            $this->addFlash('info', 'Vous êtes déjà abonné aux articles de cet auteur.');
            return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_index')));
        }

        $alert = new PostAlert();
        $alert->setUser($user);
        $alert->setAuthor($author);
        $em->persist($alert);
        $em->flush();

        $authorName = $author->getFirstName() . ' ' . $author->getLastName();
        $this->addFlash('success', 'Vous êtes maintenant abonné aux articles de ' . $authorName . '.');

        return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_index')));
    }

    #[Route('/unsubscribe/{authorId}', name: 'app_alert_unsubscribe', requirements: ['authorId' => '\d+'], methods: ['POST'])]
    public function unsubscribe(
        Request $request,
        int $authorId,
        AuthorRepository $authorRepository,
        PostAlertRepository $postAlertRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('alert_unsubscribe_' . $authorId, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_alert_list')));
        }

        $author = $authorRepository->find($authorId);
        if (!$author) {
            $this->addFlash('error', 'Auteur non trouvé.');
            return $this->redirectToRoute('app_alert_list');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $alert = $postAlertRepository->findOneByUserAndAuthor($user, $author);
        if ($alert) {
            $em->remove($alert);
            $em->flush();
            $authorName = $author->getFirstName() . ' ' . $author->getLastName();
            $this->addFlash('success', 'Vous n\'êtes plus abonné aux articles de ' . $authorName . '.');
        }

        return $this->redirect($request->headers->get('Referer', $this->generateUrl('app_alert_list')));
    }
}
