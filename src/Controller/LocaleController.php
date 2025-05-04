<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    /**
     * Change la langue de l'interface
     */
    #[Route('/change-locale/{locale}', name: 'change_locale')]
    public function changeLocale(Request $request, string $locale): Response
    {
        // Vérifier que la locale demandée est supportée
        if (!in_array($locale, ['fr', 'en', 'ar'])) {
            $locale = 'fr'; // Valeur par défaut
        }
        
        // Stocker la locale dans la session
        $request->getSession()->set('_locale', $locale);
        
        // Récupérer l'URL de référence pour rediriger l'utilisateur vers la page où il était
        $referer = $request->headers->get('referer');
        
        if ($referer) {
            // Extraire les composants de l'URL
            $urlParts = parse_url($referer);
            $path = $urlParts['path'] ?? '/';
            
            // Déterminer si la route actuelle contient déjà une locale
            $pathParts = explode('/', trim($path, '/'));
            
            // Si le premier segment est une locale connue, le remplacer par la nouvelle locale
            if (!empty($pathParts) && in_array($pathParts[0], ['fr', 'en', 'ar'])) {
                $pathParts[0] = $locale;
                $newPath = '/' . implode('/', $pathParts);
            } else {
                // Sinon, ajouter la nouvelle locale au début du chemin
                $newPath = '/' . $locale . $path;
            }
            
            // Reconstruire l'URL complète
            $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
            $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';
            
            return $this->redirect($newPath . $query . $fragment);
        }
        
        // Si aucun referer n'est trouvé, rediriger vers la page d'accueil avec la nouvelle locale
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }
}