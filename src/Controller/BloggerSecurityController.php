<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\User;
use App\Form\BloggerRegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/blogger')]
class BloggerSecurityController extends AbstractController
{
    #[Route('/login', name: 'blogger_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User && $this->getUser()->isBlogger()) {
            return $this->redirectToRoute('blogger_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('blogger/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/login_check', name: 'blogger_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('This method is intercepted by the form_login key on your firewall.');
    }

    #[Route('/register', name: 'blogger_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if ($this->getUser() instanceof User && $this->getUser()->isBlogger()) {
            return $this->redirectToRoute('blogger_dashboard');
        }

        $user = new User();

        $form = $this->createForm(BloggerRegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Un compte existe déjà avec cet email.');
                return $this->render('blogger/security/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([User::ROLE_BLOGGER]);
            $user->setIsVerified(false);

            $author = new Author();
            $author->setFirstName($form->get('firstName')->getData());
            $author->setLastName($form->get('lastName')->getData());
            $author->setUser($user);
            $user->setAuthor($author);

            $em->persist($author);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Demande envoyée. Un administrateur doit valider votre compte pour publier des articles.');

            return $this->redirectToRoute('blogger_login');
        }

        return $this->render('blogger/security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/logout', name: 'blogger_logout', methods: ['GET', 'POST'])]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the logout key on your firewall.');
    }
}
