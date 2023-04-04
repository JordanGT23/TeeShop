<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterFormType;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    #[Route("/inscription", name:"register", methods:["GET", "POST"])]
    public function register(Request $request, UserRepository $repository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        $form = $this->createForm(RegisterFormType::class, $user)->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            # Set les propietés qui ne sont pas dans le formulaire ET obligatoires en BDD
            $user->setCreatedAt(new DateTime());
            $user->setUpdatedAt(new DateTime());
            # Set le rôles du User. Cette propiété est un array[]
            $user->setRoles(["ROLE_USER"]);

            #On doit resseter manuellement la valeur du password, car par défaut il n'est pas hashe.
            #Pour cela, on doit utlisiser une méthode de hashage appelée hashPassword()
            # Cette méthode attend 2 arguments : $user, $plainPassword
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            $repository->save($user, true);

            $this->addFlash('success', "Votre inscription a été correctement enregistrée !!!");
            
            return $this->redirectToRoute("show_home");
        }

        return $this->render("user/register_form.html.twig", [
            "form" => $form->createView()
        ]); 
    }

    
}
