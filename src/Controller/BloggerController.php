<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/blogger')]
#[IsGranted('ROLE_BLOGGER')]
class BloggerController extends AbstractController
{
    #[Route('', name: 'blogger_dashboard', methods: ['GET'])]
    public function dashboard(PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->getAuthor()) {
            return $this->redirectToRoute('blogger_profile');
        }
        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_profile');
        }

        $myPosts = $postRepository->createQueryBuilder('p')
            ->andWhere('p.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $drafts = array_filter($myPosts, fn (Post $p) => $p->getStatus() === Post::STATUS_DRAFT);
        $pending = array_filter($myPosts, fn (Post $p) => $p->getStatus() === Post::STATUS_PENDING);
        $published = array_filter($myPosts, fn (Post $p) => $p->getStatus() === Post::STATUS_PUBLISHED);

        return $this->render('blogger/dashboard.html.twig', [
            'myPosts' => $myPosts,
            'draftsCount' => count($drafts),
            'pendingCount' => count($pending),
            'publishedCount' => count($published),
        ]);
    }

    #[Route('/posts', name: 'blogger_posts', methods: ['GET'])]
    public function posts(PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('blogger_login');
        }
        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_profile');
        }

        $posts = $postRepository->createQueryBuilder('p')
            ->andWhere('p.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('blogger/posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/posts/new', name: 'blogger_post_new', methods: ['GET', 'POST'])]
    public function postNew(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isVerified()) {
            $this->addFlash('error', 'Votre compte doit être validé par un administrateur pour publier des articles.');
            return $this->redirectToRoute('blogger_dashboard');
        }
        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_dashboard');
        }

        $post = new Post();
        $post->setStatus(Post::STATUS_DRAFT);
        $post->setPublishedAt(new \DateTime());
        $post->setCreatedBy($user);
        $post->setAuthor($user->getAuthor());

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action', 'draft');
            $post->setStatus($action === 'submit' ? Post::STATUS_PENDING : Post::STATUS_DRAFT);

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', $action === 'submit'
                ? 'Article soumis pour validation.'
                : 'Brouillon enregistré.');

            return $this->redirectToRoute('blogger_posts');
        }

        return $this->render('blogger/posts/form.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/posts/{id}/edit', name: 'blogger_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function postEdit(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('blogger_login');
        }

        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_posts');
        }

        $post = $postRepository->find($id);
        if (!$post || $post->getCreatedBy()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('blogger_posts');
        }

        if (!\in_array($post->getStatus(), [Post::STATUS_DRAFT, Post::STATUS_PENDING, Post::STATUS_REJECTED], true)) {
            $this->addFlash('error', 'Cet article ne peut plus être modifié.');
            return $this->redirectToRoute('blogger_posts');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action', 'draft');
            $post->setStatus($action === 'submit' ? Post::STATUS_PENDING : Post::STATUS_DRAFT);
            $post->setRejectionReason(null);

            $em->flush();

            $this->addFlash('success', $action === 'submit'
                ? 'Article soumis pour validation.'
                : 'Brouillon enregistré.');

            return $this->redirectToRoute('blogger_posts');
        }

        return $this->render('blogger/posts/form.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/posts/{id}/submit', name: 'blogger_post_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postSubmit(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('blogger_post_submit_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('blogger_posts');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('blogger_login');
        }
        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_posts');
        }

        $post = $postRepository->find($id);
        if (!$post || $post->getCreatedBy()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('blogger_posts');
        }

        if ($post->getStatus() !== Post::STATUS_DRAFT && $post->getStatus() !== Post::STATUS_REJECTED) {
            $this->addFlash('error', 'Cet article ne peut pas être soumis.');
            return $this->redirectToRoute('blogger_posts');
        }

        $post->setStatus(Post::STATUS_PENDING);
        $post->setRejectionReason(null);
        $em->flush();

        $this->addFlash('success', 'Article soumis pour validation.');

        return $this->redirectToRoute('blogger_posts');
    }

    #[Route('/posts/{id}/delete', name: 'blogger_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postDelete(Request $request, int $id, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('blogger_post_delete_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('blogger_posts');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('blogger_login');
        }
        if ($user->isBlocked()) {
            $this->addFlash('error', 'Votre compte a été bloqué. Contactez l\'administrateur.');
            return $this->redirectToRoute('blogger_posts');
        }

        $post = $postRepository->find($id);
        if (!$post || $post->getCreatedBy()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('blogger_posts');
        }

        if ($post->getStatus() === Post::STATUS_PUBLISHED) {
            $this->addFlash('error', 'Un article publié ne peut pas être supprimé.');
            return $this->redirectToRoute('blogger_posts');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Article supprimé.');

        return $this->redirectToRoute('blogger_posts');
    }

    #[Route('/profile', name: 'blogger_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('blogger_login');
        }

        $author = $user->getAuthor();
        if (!$author) {
            $author = new \App\Entity\Author();
            $author->setFirstName('Nouveau');
            $author->setLastName('Blogger');
            $author->setUser($user);
            $user->setAuthor($author);
            $em->persist($author);
            $em->flush();
        }

        $form = $this->createForm(\App\Form\ProfileType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');
            return $this->redirectToRoute('blogger_profile');
        }

        return $this->render('blogger/profile.html.twig', [
            'form' => $form,
        ]);
    }
}
