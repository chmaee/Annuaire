<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Exception\CodeAlreadyUsedException;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use App\Service\UtilisateurManager;
use App\Service\UtilisateurManagerInterface;
use App\Service\UtilisateurService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UtilisateurController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UtilisateurRepository $utilisateurRepository;
    private UtilisateurManagerInterface $utilisateurManager;
    private UtilisateurService $utilisateurService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        UtilisateurManager $utilisateurManager,
        UtilisateurService $utilisateurService
    ) {
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->utilisateurManager = $utilisateurManager;
        $this->utilisateurService = $utilisateurService;
    }

    #[Route('/', name: 'homepage')]
    public function homepage(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $utilisateurs = $this->utilisateurRepository->findAll();
        } else {
            $utilisateurs = $this->utilisateurRepository->findBy(['visible' => true]);
        }

        return $this->render('homepage.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }


    #[Route('/utilisateurs', name: 'app_utilisateur_list')]
    public function list(): Response
    {
        $utilisateurs = $this->utilisateurRepository->findAll();
        return $this->render('utilisateur/list.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/utilisateurs/{login}', name: 'app_utilisateur_show', methods: ['GET'])]
    public function show(string $login): Response
    {
        $utilisateur = $this->utilisateurRepository->findOneBy(['login' => $login]);

        if (!$utilisateur) {
            $this->addFlash('error', 'Utilisateur inexistant');
            return $this->redirectToRoute('homepage');
        }

        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur
        ]);
    }


    #[Route('/utilisateur/creer', name: 'app_utilisateur_create', methods: ['GET', 'POST'])]
    #[Route('/utilisateur/{login}/modifier', name: 'app_utilisateur_edit', methods: ['GET', 'POST'])]
    public function form(Request $request, ?string $login = null): Response
    {
        $user = $this->getUser();
        $isCurrentUser = $login && $user && $user->getLogin() === $login;

        if ($login) {
            $utilisateur = $this->utilisateurRepository->findOneBy(['login' => $login]);

            if (!$utilisateur) {
                throw $this->createNotFoundException("Aucun utilisateur trouvé pour le login '$login'");
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cet utilisateur.");
            }

            if (in_array('ROLE_ADMIN', $utilisateur->getRoles()) || in_array('ROLE_SUPER_ADMIN', $utilisateur->getRoles())) {
                if (!$isCurrentUser) {
                    throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cet utilisateur.");
                }
            }

            if (!$this->isGranted('USER_EDIT', $utilisateur) && !$isCurrentUser) {
                throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cet utilisateur.");
            }
        } else {
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à créer un utilisateur.");
            }
            $utilisateur = new Utilisateur();
        }
        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'is_edit' => $login !== null,
            'isCurrentUser' => $isCurrentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $request->isMethod('POST')) {
            $code = $form->get('code')->getData();

            if ($utilisateur->getId() === null) {
                if (!$code) {
                    $code = $this->utilisateurService->generateUniqueCode(null);
                    $utilisateur->setCode($code);
                } else {
                    $utilisateur->setCode($code);
                }
            } else {
                if (!$code) {
                    $code = $utilisateur->getCode();
                } else {
                    $utilisateur->setCode($code);
                }
            }
            if ($utilisateur->getId() !== null) {
                $utilisateur->setTelephone($form->get('telephone')->getData());
            }
            if ($utilisateur->getId() === null) {
                $newPassword = $form->get('plainPassword')->getData();
                if ($newPassword) {
                    $utilisateur->setPassword($newPassword);
                } else {
                    $this->addFlash('error', 'Le mot de passe est requis lors de la création.');
                    return $this->render('utilisateur/form.html.twig', [
                        'form' => $form->createView(),
                        'isEdit' => false,
                        'utilisateur' => $utilisateur,
                        'isCurrentUser' => $isCurrentUser,
                    ]);
                }
                try {
                    $this->utilisateurService->createUtilisateur(
                        $utilisateur->getLogin(),
                        $utilisateur->getAdresseEmail(),
                        $newPassword,
                        $utilisateur->getCode(),
                        $utilisateur->isVisible()
                    );
                } catch (CodeAlreadyUsedException $e) {
                    $this->addFlash('error', $e->getMessage());

                    return $this->render('utilisateur/form.html.twig', [
                        'form' => $form->createView(),
                        'isEdit' => false,
                        'utilisateur' => $utilisateur,
                        'isCurrentUser' => $isCurrentUser,
                    ]);
                }
            } else {
                // Gestion de la modification
                if ($isCurrentUser) {
                    $oldPassword = $form->get('oldPassword')->getData();
                    $newPassword = $form->get('newPassword')->getData();

                    if ($newPassword) {
                        if (!password_verify($oldPassword, $utilisateur->getPassword())) {
                            $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
                            return $this->render('utilisateur/form.html.twig', [
                                'form' => $form->createView(),
                                'isEdit' => true,
                                'utilisateur' => $utilisateur,
                                'isCurrentUser' => $isCurrentUser,
                            ]);
                        }

                        if ($oldPassword === $newPassword) {
                            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
                            return $this->render('utilisateur/form.html.twig', [
                                'form' => $form->createView(),
                                'isEdit' => true,
                                'utilisateur' => $utilisateur,
                                'isCurrentUser' => $isCurrentUser,
                            ]);
                        }
                        $utilisateur->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
                    }
                }
                try {
                    $this->utilisateurService->updateUtilisateur($utilisateur);
                } catch (CodeAlreadyUsedException $e) {
                    $this->addFlash('error', $e->getMessage());

                    return $this->render('utilisateur/form.html.twig', [
                        'form' => $form->createView(),
                        'isEdit' => true,
                        'utilisateur' => $utilisateur,
                        'isCurrentUser' => $isCurrentUser,
                    ]);
                }
            }

            $this->addFlash('success', 'Utilisateur enregistré avec succès.');
            return $this->redirectToRoute('app_utilisateur_show', ['login' => $utilisateur->getLogin()]);
        }

        return $this->render('utilisateur/form.html.twig', [
            'form' => $form->createView(),
            'isEdit' => $utilisateur->getId() !== null,
            'isCurrentUser' => $isCurrentUser,
            'utilisateur' => $utilisateur,
        ]);
    }


    #[Route('/utilisateur/{login}/supprimer', name: 'app_utilisateur_delete', methods: ['POST'])]
    public function delete(Utilisateur $utilisateur, TokenStorageInterface $tokenStorage): Response
    {
        if (!$this->isGranted('USER_DELETE', $utilisateur)) {
            $this->addFlash('error', 'Vous n’êtes pas autorisé à supprimer cet utilisateur.');
            return $this->redirectToRoute('homepage');
        }

        $this->utilisateurService->deleteUtilisateur($utilisateur);

        if ($this->getUser() === $utilisateur) {
            $tokenStorage->setToken(null);
        }

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('homepage');
    }


    #[Route('/inscription', name: 'app_inscription', methods: ['GET', 'POST'])]
    public function inscription(Request $request): Response
    {
        // Redirection si l'utilisateur est déjà connecté
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('homepage');
        }

        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->get('code')->getData();
            $plainPassword = $form->get('plainPassword')->getData();

            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est requis.');
                return $this->render('utilisateur/inscription.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            try {
                $this->utilisateurService->createUtilisateur(
                    $utilisateur->getLogin(),
                    $utilisateur->getAdresseEmail(),
                    $plainPassword,
                    $code,
                    $utilisateur->isVisible()
                );

                $this->addFlash('success', 'Inscription réussie !');
                return $this->redirectToRoute('homepage');

            } catch (CodeAlreadyUsedException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('utilisateur/inscription.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('utilisateur/inscription.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/connexion', name: 'app_connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authenticationUtils, SessionInterface $session): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('homepage');
        }

        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            if (str_contains($error->getMessageKey(), 'Invalid credentials')) {
                $session->getFlashBag()->add('error', 'Nom d\'utilisateur ou mot de passe incorrect.');
            } else {
                $session->getFlashBag()->add('error', 'Erreur lors de la connexion. Veuillez réessayer.');
            }
        }
        return $this->render('utilisateur/connexion.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route('/verifier-code', name: 'app_verifier_code', methods: ['POST'], options: ['expose' => true])]
    public function verifierCode(Request $request, UtilisateurService $utilisateurService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;
        $login = $data['login'] ?? null;

        if ($code === null) {
            return new JsonResponse(['available' => false, 'error' => 'Code manquant'], 400);
        }

        if ($login === null || $login === '') {
            // Cas de la création d'un nouvel utilisateur
            $isUnique = $utilisateurService->isCodeUnique($code);
        } else {
            // Cas de la modification d'un utilisateur existant
            $isUnique = $utilisateurService->isCodeUniqueForExistingUser($code, $login);
        }

        return new JsonResponse(['available' => $isUnique]);
    }

    #[Route('/maintenance', name: 'app_maintenance_page')]
    public function maintenance(): Response
    {
        return $this->render('maintenance.html.twig');
    }

    #[Route('/utilisateur/{login}/json', name: 'app_utilisateur_json', methods: ['GET'], options: ['expose' => true])]
    public function getUtilisateurJson(string $login, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $utilisateur = $utilisateurRepository->findOneBy(['login' => $login]);

        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = [
            'login' => $utilisateur->getLogin(),
            'adresseEmail' => $utilisateur->getAdresseEmail(),
            'telephone' => $utilisateur->getTelephone(),
            'visible' => $utilisateur->isVisible(),
            'code' => $utilisateur->getCode(),
        ];

        return new JsonResponse($data);
    }


    #[Route('/utilisateur/{login}/promouvoir-admin', name: 'app_utilisateur_promouvoir_admin')]
    public function promouvoirAdmin(Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return $this->redirectToRoute('homepage');
        }

        if (in_array('ROLE_ADMIN', $utilisateur->getRoles()) || in_array('ROLE_SUPER_ADMIN', $utilisateur->getRoles())) {
            $this->addFlash('error', 'Vous ne pouvez pas promouvoir cet utilisateur en administrateur.');
            return $this->redirectToRoute('app_utilisateur_list');
        }

        $utilisateur->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();
        $this->addFlash('success', sprintf('L\'utilisateur %s a été promu administrateur.', $utilisateur->getLogin()));


        return $this->redirectToRoute('app_utilisateur_list');
    }

    #[Route('/utilisateur/{login}/retrograder-admin', name: 'app_utilisateur_retrograder_admin')]
    public function retrograderAdmin(Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return $this->redirectToRoute('homepage');
        }

        if (!in_array('ROLE_ADMIN', $utilisateur->getRoles())) {
            $this->addFlash('error', 'Cet utilisateur n\'est pas administrateur.');
            return $this->redirectToRoute('app_utilisateur_list');
        }

        if (in_array('ROLE_SUPER_ADMIN', $utilisateur->getRoles())) {
            $this->addFlash('error', 'Vous ne pouvez pas rétrograder un super administrateur.');
            return $this->redirectToRoute('app_utilisateur_list');
        }

        $utilisateur->setRoles(['ROLE_USER']);
        $entityManager->flush();
        $this->addFlash('success', sprintf('L\'utilisateur %s n\'est plus un administrateur.', $utilisateur->getLogin()));

        return $this->redirectToRoute('app_utilisateur_list');
    }
}