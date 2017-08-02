<?php

namespace PhpTaxonomy\MultiTaxonomy\DbalUtil\Pagerfanta\Twig\Controller;

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
    // New in version 3.2: The functionality to get the user via the method signature was introduced in Symfony 3.2. You can still retrieve it by calling $this->getUser() if you extend the Controller class.
    // http://symfony.com/doc/current/security.html#retrieving-the-user-object

use Symfony\Component\Templating\EngineInterface;
// TODO: Investgate Twig dependency "symfony/twig-bundle": "^2.7 || ^3.0 || ^4.0"
// TODO: Remove Twig in filename
// https://symfony.com/doc/current/components/templating.html

/*
 * Default controller.
 *
 * @Route("taxonomy")
 */
class MultiTaxonomyController // extends FrameworkAbstractController
{
    /*
     * Lists all taxonomy tree entities.
     *
     * @Route("/", name="taxonomy_index")
     * @Method("GET")
     */
    public static function indexAction(
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

    
    /*
     * Creates a new taxonomy entity.
     *
     * @Route("/new", name="taxonomy_new")
     * @Method({"GET", "POST"})
     */
    public static function newAction(
        Request $request, // used by form
        UserInterface $user,
        UrlGeneratorInterface $urlGenerator,
        \RaphiaDBAL $model,
        FormFactoryInterface $formFactory,
        EngineInterface $templating
    )
    {
        $form = $formFactory->create(Form::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // => do something for allowing to enter synonyms
            $taxonomy_leaf = $model->insert_default_values_returning_uuid('taxonomy_leaf');
            $to_insert = $form->getData();
            $to_insert['synonym_uuid'] = $taxonomy_leaf['uuid'];
            $taxonomy_tree_uuid = $model->insert_returning_uuid('taxonomy_tree', $to_insert)['uuid'];
            $model->namespace_insert('link_taxonomy_tree_user', [
                'taxonomy_tree_uuid' => $taxonomy_tree_uuid,
                'user_uuid' => $user->getId(),
            ], $user->getId(), $taxonomy_tree_uuid);

            return new RedirectResponse($urlGenerator->generate('taxonomy_show', ['uuid' => $taxonomy_tree_uuid]));
        }

        return new Response($templating->render('@MultiTaxonomyDbalUtilBundle/new.html.twig', [
            'form' => $form->createView(),
        ]));
    }


    /*
     * Displays a form to edit an existing taxonomy entity.
     *
     * @Route("/edit/{uuid}", name="taxonomy_edit")
     * @Method({"GET", "POST"})
     */
    public static function editAction(
        $uuid,
        Request $request, // used by form
        UrlGeneratorInterface $urlGenerator,
        \RaphiaDBAL $model,
        FormFactoryInterface $formFactory,
        EngineInterface $templating
    )
    {
        $uuida = ['uuid' => $uuid];
        $taxonomyTree = $model->getByUnique('taxonomy_tree', $uuida);

        $deleteForm = self::createDeleteForm($taxonomyTree, $urlGenerator, $formFactory);
        $editForm = $formFactory->create(Form::class, $taxonomyTree);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $to_update = $editForm->getData(); // TODO: SECURITY review everythere, the possibility of abuse, by changing important fields like id!
            $model->updateByUnique('taxonomy_tree', $uuida, ['term' => $to_update['term']]);

            return new RedirectResponse($urlGenerator->generate('taxonomy_show', $uuida));
        }

        return new Response($templating->render('@MultiTaxonomyDbalUtilBundle/edit.html.twig', array(
            'term' => $taxonomyTree,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        )));
    }


    /*
     * Finds and displays a taxonomy entity.
     *
     * @Route("/{uuid}", name="taxonomy_show")
     * @Method("GET")
     */
    public static function showAction(
        $uuid,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        \RaphiaDBAL $model,
        FormFactoryInterface $formFactory,
        EngineInterface $templating
    )
    {
        $taxonomyTree = $model->getByUnique('taxonomy_tree', ['uuid' => $uuid]);
        //^ TODO: SECURITY AUTHORIZATION

        $deleteForm = self::createDeleteForm($taxonomyTree, $urlGenerator, $formFactory);
        
        return new Response($templating->render('@MultiTaxonomyDbalUtilBundle/show.html.twig', [
            'term' => $taxonomyTree,
            'uRLs' => $model
                ->getWhereManyToManyToManyPager('taxonomy_leaf', 'uuid', 'taxonomy_uuid',
                    'link_owned_url_taxonomy', 'owned_url_uuid', 'uuid', 'owned_url', 'url_uuid', 'uuid', 'url',
                    ['uuid' => $taxonomyTree['synonym_uuid']])
                ->setMaxPerPage(100)
                ->setCurrentPage($request->query->getInt('page', 1)),
            'delete_form' => $deleteForm->createView(),
        ]));
    }


    /*
     * Deletes a taxonomy entity.
     *
     * @Route("/{uuid}", name="taxonomy_delete")
     * @Method("DELETE")
     */
    public static function deleteAction(
        $uuid,
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        \RaphiaDBAL $model,
        FormFactoryInterface $formFactory
    )
    {
        // TODO: authorization
        
        $taxonomyTree = $model->getByUnique('taxonomy_tree', ['uuid' => $uuid]);

        $form = self::createDeleteForm($taxonomyTree, $urlGenerator, $formFactory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $model->deleteByUnique('taxonomy_tree', ['uuid' => $uuid]);
        }

        return new RedirectResponse($urlGenerator->generate('taxonomy_index'));
    }
    // Anyways one should implement deletion date field before really deleting...

    /*
     * Creates a form to delete a uRL entity.
     *
     * @param array $uRL The uRL array
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private static function createDeleteForm(
        array $taxonomyTree,
        UrlGeneratorInterface $urlGenerator,
        FormFactoryInterface $formFactory
    )
    {
        return $formFactory->createBuilder()
            ->setAction($urlGenerator->generate('taxonomy_delete', $taxonomyTree))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
