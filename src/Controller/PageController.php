<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\PostAlertRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class PageController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $search = $request->query->get('q');

        $result = $postRepository->findPaginatedAndSearch($page, $search);

        return $this->render('page/index.html.twig', [
            'posts' => $result['posts'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'current_page' => $result['page'],
            'search' => $search,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, PostRepository $postRepository, PostAlertRepository $postAlertRepository, EntityManagerInterface $em): Response
    {
        $post = $postRepository->find($id);

        if (null === $post) {
            throw new NotFoundHttpException('Article non trouvé.');
        }

        if ($post->getStatus() !== Post::STATUS_PUBLISHED) {
            $createdBy = $post->getCreatedBy();
            $currentUser = $this->getUser();
            $canView = $this->isGranted('ROLE_ADMIN')
                || ($createdBy && $currentUser && $createdBy->getId() === $currentUser->getId());
            if (!$canView) {
                throw new NotFoundHttpException('Article non trouvé.');
            }
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTime());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        // AJAX: our Stimulus controller sends this header. Turbo is unreliable for form intercept.
        $isAjaxRequest = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        $isTurboRequest = !$isAjaxRequest && (
            TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()
            || str_contains($request->headers->get('Accept', ''), 'text/vnd.turbo-stream.html')
        );

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($comment);
                $em->flush();
            } catch (\Throwable $e) {
                if ($isAjaxRequest) {
                    return $this->json(['error' => 'Une erreur s\'est produite. Veuillez réessayer.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                if ($isTurboRequest) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                    return new Response(
                        $this->renderBlock('page/show.html.twig', 'error_stream', [
                            'errorMessage' => 'Une erreur s\'est produite. Veuillez réessayer.',
                        ]),
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        ['Content-Type' => 'text/vnd.turbo-stream.html']
                    );
                }
                $this->addFlash('error', 'Une erreur s\'est produite. Veuillez réessayer.');

                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
            }

            if ($isAjaxRequest) {
                $emptyComment = new Comment();
                $emptyComment->setPost($post);
                $emptyComment->setCreatedAt(new \DateTime());
                $emptyForm = $this->createForm(CommentType::class, $emptyComment);

                $commentHtml = $this->renderView('page/_comment.html.twig', ['comment' => $comment]);
                $formHtml = $this->renderView('page/_comment_form.html.twig', [
                    'form' => $emptyForm->createView(),
                    'post' => $post,
                ]);

                return $this->json([
                    'commentHtml' => $commentHtml,
                    'formHtml' => $formHtml,
                ]);
            }

            if ($isTurboRequest) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                $emptyComment = new Comment();
                $emptyComment->setPost($post);
                $emptyComment->setCreatedAt(new \DateTime());
                $emptyForm = $this->createForm(CommentType::class, $emptyComment);

                return new Response(
                    $this->renderBlock('page/show.html.twig', 'success_stream', [
                        'comment' => $comment,
                        'form' => $emptyForm->createView(),
                        'post' => $post,
                    ]),
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/vnd.turbo-stream.html']
                );
            }

            $this->addFlash('success', 'Votre commentaire a été publié.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        if ($form->isSubmitted() && $isAjaxRequest) {
            $formHtml = $this->renderView('page/_comment_form.html.twig', [
                'form' => $form->createView(),
                'post' => $post,
            ]);

            return $this->json(['formHtml' => $formHtml, 'errors' => true], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($form->isSubmitted() && $isTurboRequest) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return new Response(
                $this->renderBlock('page/show.html.twig', 'validation_stream', [
                    'form' => $form->createView(),
                    'post' => $post,
                ]),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['Content-Type' => 'text/vnd.turbo-stream.html']
            );
        }

        $subscribedToAuthor = false;
        $user = $this->getUser();
        $author = $post->getAuthor();
        if ($user && $author) {
            $subscribedToAuthor = null !== $postAlertRepository->findOneByUserAndAuthor($user, $author);
        }

        return $this->render('page/show.html.twig', [
            'post' => $post,
            'form' => $form,
            'subscribed_to_author' => $subscribedToAuthor,
        ]);
    }

    #[Route('/legal', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('page/legal.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }
}
