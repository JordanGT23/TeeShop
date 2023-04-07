<?php

namespace App\Controller;

use DateTime;
use App\Entity\Command;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/panier')]
class PanierController extends AbstractController
{
    #[Route('/voir-mon-panier', name: 'show_panier', methods: ['GET'])]
    public function showPanier(SessionInterface $session): Response
    {
        # Cette instruction permet de récuperer le panier en session, et à défaut créer le panier s'il n'existe pas
        $panier = $session->get('panier', []);
        $total = 0;

        # Exercice: Faire le code qui permet calculer le total par produit et le tolal du panier
        foreach ($panier as $item) {
            $totalItem = $item['product']->getPrice() * $item['quantity'];
            $total += $totalItem;
        }

        # Céer l'action qui va rendre la vue 'panier/show_panier.html.twig'
        return $this->render('panier/show_panier.html.twig', [
            'total' => $total
        ]);
    }


    #[Route('/ajouter-un-produit/{id}', name: 'add_item', methods: ['GET'])]
    public function addItem(Product $product, SessionInterface $session): Response
    {
        # Si dans la session le panier n'existe pas la méthode GET() retournera le second argument : un array vide
        $panier = $session->get('panier', []);

        if (empty($panier[$product->getId()])) {
            $panier[$product->getId()]['quantity'] = 1;
            $panier[$product->getId()]['product'] = $product;
        } else {
            ++$panier[$product->getId()]['quantity'];
            // post-incrementation: quantity +1
            // pre-incrementation: 1 + quantity
        }

        # Ici, nous devons set() le panier en session, en lui passant $panier[]
        $session->set('panier', $panier);

        $this->addFlash('success', "Le produit a bien été ajouté a votre panier !");
        return $this->redirectToRoute('show_home');
    }

    #[Route('/vider-le-panier', name: 'delete_panier', methods: ['GET'])]
    public function deletePanier(SessionInterface $session)
    {
        $session->remove('panier');

        $this->addFlash('success', "Votre panier est à nouveau vide.");
        return $this->redirectToRoute('show_home');
    }

    # Exercice:: creer l'action qui permet suprimer un seul item du panier
    #[Route('/retirer-du-panier/{id}', name: 'delete_item', methods: ['GET'])]
    public function deleteItem(int $id, SessionInterface $session): Response
    {
        $panier = $session->get('panier');
        // array_key_exists() est une fonction native de PHP, qui permet de vérifier si une key existe dans un array.
        if (array_key_exists($id, $panier)) {
            unset($panier[$id]); # unset() permet de supprimer une variable (ou une clé dans un array)
        }

        $session->set('panier', $panier);
        return $this->redirectToRoute('show_panier');
    }

    #[Route('/valider-mon-panier', name: 'validate_commande', methods: ['GET'])]
    public function validateCommande(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('show_panier');
        }

        $commande = new Command();

        $commande->setCreatedAt(new DateTime());
        $commande->setUpdatedAt(new DateTime());

        $total = 0;


        foreach ($panier as $item) {
            $totalItem = $item['product']->getPrice() * $item['quantity'];
            $total += $totalItem;

            $commande->setQuantity($item['quantity']);
            //            $product = $item['product'];
        }

        //        $commande->setProduct($product);


        $commande->setStatus('en préparation');
        $commande->setUser($this->getUser());
        $commande->setTotal($total);

        $entityManager->persist($commande);
        $entityManager->flush();

        $session->remove('panier');

        $this->addFlash('success', "Bravo, votre commande est prise en compte et en préparation. Vous pouvez la retrouver dans Mes Commandes.");
        return $this->redirectToRoute('show_home');
    } // end function validate()
}
