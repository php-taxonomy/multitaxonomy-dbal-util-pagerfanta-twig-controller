<?php

namespace PhpTaxonomy\MultiTaxonomy\DoctrineDbalUtil\Pagerfanta\Twig\Controller;

// use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
// use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
// "sensio/framework-extra-bundle":"^3.0 || ^4.0",

use Symfony\Component\Form\FormFactoryInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
    // New in version 3.2: The functionality to get the user via the method signature was introduced in Symfony 3.2.
    // You can still retrieve it by calling $this->getUser() if you extend the Controller class.
    // http://symfony.com/doc/current/security.html#retrieving-the-user-object

use Symfony\Component\Templating\EngineInterface;

/*
 * Default controller.
 *
 * @Route("taxonomy")
 */
class IndexController // extends FrameworkAbstractController
{
    /*
     * Lists all taxonomy tree entities.
     *
     * @Route("/", name="taxonomy_index")
     * @Method("GET")
     */
    public function __invoke(
        Request $request, // used by pager
        UserInterface $user,
        \RaphiaDBAL $model,
        EngineInterface $templating
    )
    {
        return new Response($templating->render('@MultiTaxonomyDbalUtilBundle/index.html.twig', [ // why not a @string related to controller package?
            'terms' => $model
                ->getManyToManyWherePager('taxonomy_tree', 'uuid',
                    'taxonomy_tree_uuid', 'link_taxonomy_tree_user', 'user_uuid',
                    'uuid', 'http_user', ['uuid' => $user->getId()], 'base.term')
                ->setMaxPerPage(2) // 100
                ->setCurrentPage($request->query->getInt('page', 1))
            ,
        ]));
    }
}
