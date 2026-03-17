<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\AdminPostType;
use App\Repository\PostAlertRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\TemplatedEmail;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(PostRepository $postRepository, UserRepository $userRepository): Response
    {
        $totalPosts = $postRepository->count([]);
        $pendingPosts = $postRepository->count(['status' => Post::STATUS_PENDING]);
        $pendingBloggers = count($userRepository->findUnverifiedBloggers());

        $recentPosts = $postRepository->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'totalPosts' => $totalPosts,
            'pendingPosts' => $pendingPosts,
            'pendingBloggers' => $pendingBloggers,
            'recentPosts' => $recentPosts,
        ]);
    }

    #[Route('/posts/pending', name: 'admin_posts_pending', methods: ['GET'])]
    public function postsPending(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy(
            ['status' => Post::STATUS_PENDING],
            ['publishedAt' => 'DESC']
        );

        return $this->render('admin/posts/pending.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/posts', name: 'admin_posts_all', methods: ['GET'])]
    public function postsAll(Request $request, PostRepository $postRepository): Response
    {
        $status = $request->query->get('status');
        $qb = $postRepository->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC');

        if ($status && \in_array($status, [Post::STATUS_DRAFT, Post::STATUS_PENDING, Post::STATUS_PUBLISHED, Post::STATUS_REJECTED], true)) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }

        $posts = $qb->getQuery()->getResult();

        return $this->render('admin/posts/all.html.twig', [
            'posts' => $posts,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/posts/{id}/edit', name: 'admin_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function postEdit(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $post = $postRepository->find($id);
        if (!$post) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('admin_posts_all');
        }

        $form = $this->createForm(AdminPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Article mis à jour.');
            return $this->redirectToRoute('admin_posts_all');
        }

        return $this->render('admin/posts/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'admin_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postDelete(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_post_delete_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_posts_all');
        }
        $post = $postRepository->find($id);
        if (!$post) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('admin_posts_all');
        }

        $em->remove($post);
        $em->flush();
        $this->addFlash('success', 'Article supprimé.');

        return $this->redirectToRoute('admin_posts_all');
    }

    #[Route('/posts/{id}/approve', name: 'admin_post_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postApprove(
        Request $request,
        int $id,
        PostRepository $postRepository,
        PostAlertRepository $postAlertRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        if (!$this->isCsrfTokenValid('admin_post_approve_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_posts_pending');
        }
        $post = $postRepository->find($id);
        if (!$post || $post->getStatus() !== Post::STATUS_PENDING) {
            $this->addFlash('error', 'Article non trouvé ou déjà traité.');
            return $this->redirectToRoute('admin_posts_pending');
        }

        $post->setStatus(Post::STATUS_PUBLISHED);
        $post->setRejectionReason(null);
        $post->setPublishedAt(new \DateTime());
        $em->flush();

        $author = $post->getAuthor();
        if ($author) {
            $subscribers = $postAlertRepository->findByAuthor($author);
            $authorName = $author->getFirstName() . ' ' . $author->getLastName();
            $postUrl = $urlGenerator->generate('app_post_show', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $manageAlertsUrl = $urlGenerator->generate('app_alert_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $excerpt = $post->getExcerpt() ?: mb_substr(strip_tags($post->getContent()), 0, 200) . '...';

            foreach ($subscribers as $alert) {
                $user = $alert->getUser();
                if ($user && $user->getEmail()) {
                    try {
                        $email = (new TemplatedEmail())
                            ->from('noreply@example.com')
                            ->to($user->getEmail())
                            ->subject('Nouvel article de ' . $authorName . ' : ' . $post->getTitle())
                            ->htmlTemplate('email/post_alert.html.twig')
                            ->textTemplate('email/post_alert.txt.twig')
                            ->context([
                                'authorName' => $authorName,
                                'postTitle' => $post->getTitle(),
                                'excerpt' => $excerpt,
                                'postUrl' => $postUrl,
                                'manageAlertsUrl' => $manageAlertsUrl,
                            ]);
                        $mailer->send($email);
                    } catch (\Throwable $e) {
                        // Log but continue: don't block approval if one email fails
                    }
                }
            }
        }

        $this->addFlash('success', 'Article approuvé et publié.');

        return $this->redirectToRoute('admin_posts_pending');
    }

    #[Route('/posts/{id}/reject', name: 'admin_post_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postReject(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_post_reject_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_posts_pending');
        }
        $post = $postRepository->find($id);
        if (!$post || $post->getStatus() !== Post::STATUS_PENDING) {
            $this->addFlash('error', 'Article non trouvé ou déjà traité.');
            return $this->redirectToRoute('admin_posts_pending');
        }

        $reason = $request->request->get('rejection_reason', '');

        $post->setStatus(Post::STATUS_REJECTED);
        $post->setRejectionReason($reason ?: null);
        $em->flush();

        $this->addFlash('success', 'Article rejeté.');

        return $this->redirectToRoute('admin_posts_pending');
    }

    #[Route('/bloggers', name: 'admin_bloggers_all', methods: ['GET'])]
    public function bloggersAll(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBloggers();

        return $this->render('admin/bloggers/all.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/bloggers/{id}/block', name: 'admin_blogger_block', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function bloggerBlock(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_blogger_block_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_bloggers_all');
        }
        $user = $userRepository->find($id);
        if (!$user || !$user->isBlogger()) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('admin_bloggers_all');
        }

        $user->setIsBlocked(!$user->isBlocked());
        $em->flush();

        $this->addFlash('success', $user->isBlocked() ? 'Blogger bloqué.' : 'Blogger débloqué.');

        return $this->redirectToRoute('admin_bloggers_all');
    }

    #[Route('/bloggers/pending', name: 'admin_bloggers_pending', methods: ['GET'])]
    public function bloggersPending(UserRepository $userRepository): Response
    {
        $users = $userRepository->findUnverifiedBloggers();

        return $this->render('admin/bloggers/pending.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/bloggers/{id}/approve', name: 'admin_blogger_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function bloggerApprove(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_blogger_approve_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_bloggers_pending');
        }
        $user = $userRepository->find($id);
        if (!$user || $user->isVerified()) {
            $this->addFlash('error', 'Utilisateur non trouvé ou déjà validé.');
            return $this->redirectToRoute('admin_bloggers_pending');
        }

        $user->setIsVerified(true);
        $em->flush();

        $this->addFlash('success', 'Blogger validé.');

        return $this->redirectToRoute('admin_bloggers_pending');
    }

    #[Route('/bloggers/{id}/reject', name: 'admin_blogger_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function bloggerReject(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_blogger_reject_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_bloggers_pending');
        }
        $user = $userRepository->find($id);
        if (!$user || $user->isVerified()) {
            $this->addFlash('error', 'Utilisateur non trouvé ou déjà validé.');
            return $this->redirectToRoute('admin_bloggers_pending');
        }

        $author = $user->getAuthor();
        if ($author) {
            $em->remove($author);
        }
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Demande de blogger rejetée.');

        return $this->redirectToRoute('admin_bloggers_pending');
    }
}
