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
    protected $model;
    protected $templating;
    protected $template;

    public function __construct(
        \RaphiaDBAL $model, // TODO: Change for this controller's view viewmodel.
        EngineInterface $templating,
        // $template = 'index.html.twig'
        $template = '@MultiTaxonomyDbalUtilBundle/index.html.twig' // TODO: Change for generic and configurate in DI container.
        // Question: is it better to have a reusable generic name for the template or to make it point to a help page for its configuration?
    )
    {
        $this->model = $model;
        $this->templating = $templating;
        $this->template = $template;
    }

    public function __invoke(
        UserInterface $user,
        Request $request = new Request() // used by pager
    )
    {
        return new Response($templating->render($this->template, [ // why not a @string related to controller package?
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
