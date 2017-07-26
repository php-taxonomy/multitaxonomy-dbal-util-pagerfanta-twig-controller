<?php

namespace PhpTaxonomy\MultiTaxonomy\DbalUtil\Pagerfanta\Twig\Controller;

// use AppBundle\Form\TaxonomyForm;
// use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
    // New in version 3.2: The functionality to get the user via the method signature was introduced in Symfony 3.2. You can still retrieve it by calling $this->getUser() if you extend the Controller class.
    // http://symfony.com/doc/current/security.html#retrieving-the-user-object

/**
 * Default controller.
 *
 * @Route("taxonomy")
 */
class Default extends Controller
{
    /**
     * Lists all taxonomy tree entities.
     *
     * @Route("/", name="taxonomy_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, UserInterface $user) //, Connection $conn)
    // http://symfony.com/doc/current/doctrine/dbal.html
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }
        //^ http://symfony.com/doc/current/security.html#checking-to-see-if-a-user-is-logged-in-is-authenticated-fully

        // $this
        //         ->container->get('raphia_model')
        //         ->getManyToManyWherePager('taxonomy_tree', 'uuid', 'taxonomy_tree_uuid', 'link_taxonomy_tree_user', 'user_uuid', 'uuid', 'user', ['uuid' => $user->getId()])
        //         ->setMaxPerPage(2)
        //         ->setCurrentPage($request->query->getInt('page', 1))
        // ;

        // return $this->render('@MultiTaxonomyDbalUtilBundle/index.html.twig', [
        //     'terms' => $this
        //         ->container->get('raphia_model')
        //         ->getManyToManyWhereTraversable('taxonomy_tree', 'uuid', 'taxonomy_tree_uuid', 'link_taxonomy_tree_user', 'user_uuid', 'uuid', 'user', ['uuid' => $user->getId()]),
        // ]);
        // dump('index action');
        $conn = $this->container->get('database_connection');
        return $this->render('@MultiTaxonomyDbalUtilBundle/index.html.twig', [
            'terms' => $this
                ->container->get('raphia_model')
                ->getManyToManyWherePager('taxonomy_tree', 'uuid',
                    'taxonomy_tree_uuid', 'link_taxonomy_tree_user', 'user_uuid',
                    // 'uuid', $conn->quoteIdentifier('user'), ['uuid' => $user->getId()], 'base.term')
                    'uuid', 'http_user', ['uuid' => $user->getId()], 'base.term')
                ->setMaxPerPage(2) // 100
                ->setCurrentPage($request->query->getInt('page', 1))
            ,
        ]);
    }

    /**
     * Creates a new taxonomy entity.
     *
     * @Route("/new", name="taxonomy_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, UserInterface $user)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $model = $this->container->get('raphia_model');

        $form = $this->createForm(Form::class);
        // $form = $this->container->get('form.factory')->create(URLForm::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // => do something for allowing to enter synonyms
            $taxonomy_leaf = $model->insert_default_values_returning_uuid('taxonomy_leaf');
            $to_insert = $form->getData();
            $to_insert['synonym_uuid'] = $taxonomy_leaf['uuid']; // $model->lastInsertId('taxonomy_id_seq'); // TODO: specific to Postgres
            $taxonomy_tree_uuid = $model->insert_returning_uuid('taxonomy_tree', $to_insert)['uuid'];
            $model->namespace_insert('link_taxonomy_tree_user', [
                'taxonomy_tree_uuid' => $taxonomy_tree_uuid,
                'user_uuid' => $user->getId(),
            ], $user->getId(), $taxonomy_tree_uuid);

            return $this->redirectToRoute('taxonomy_show', ['uuid' => $taxonomy_tree_uuid]);
        }

        return $this->render('@MultiTaxonomyDbalUtilBundle/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    // SELECT CASE EXISTS (SELECT uuid FROM url WHERE url = 'http://php.net/')
    //     WHEN false THEN (INSERT INTO url (uuid, url) VALUES (uuid_generate_v5(uuid_ns_url(), 'http://php.net/'), 'http://php.net/') RETURNING uuid)
    //     WHEN true  THEN (SELECT uuid FROM url WHERE url = 'http://php.net/')
    //     ELSE (INSERT INTO url (uuid, url) VALUES (uuid_generate_v5(uuid_ns_url(), 'http://php.net/'), 'http://php.net/') RETURNING uuid)
    // END;
    
    // INSERT INTO url (uuid, url) VALUES (uuid_generate_v5(uuid_ns_url(), 'http://php.net/'), 'http://php.net/')
    //     ON CONFLICT (uuid) DO NOTHING RETURNING uuid;


    /**
     * Displays a form to edit an existing taxonomy entity.
     *
     * @Route("/edit/{uuid}", name="taxonomy_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $uuid)
    // public function editAction(Request $request, URL $uRL)
    {
        $uuida = ['uuid' => $uuid];
        $model = $this->container->get('raphia_model');
        $taxonomyTree = $model->getByUnique('taxonomy_tree', $uuida);
        // $uRL = ['url' => $model->getByUnique('url', ['uuid' => $uRL['url_uuid']])['url']];

        $deleteForm = $this->createDeleteForm($taxonomyTree);
        $editForm = $this->createForm(Form::class, $taxonomyTree);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $to_update = $editForm->getData(); // TODO: SECURITY review everythere, the possibility of abuse, by changing important fields like id!
            $model->updateByUnique('taxonomy_tree', $uuida, ['term' => $to_update['term']]);

            return $this->redirectToRoute('taxonomy_show', $uuida);
        }

        return $this->render('@MultiTaxonomyDbalUtilBundle/edit.html.twig', array(
            'term' => $taxonomyTree,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    // if ($uRL['url_uuid'] <> $url_uuid):
    // SELECT CASE EXISTS (SELECT * FROM owned_url WHERE url_uuid = $uRL['url_uuid'])
    //     WHEN false THEN (DELETE url WHERE uuid = $uRL['url_uuid'])
    // END;
    // To insert just before redirection


    /**
     * Finds and displays a taxonomy entity.
     *
     * @Route("/{uuid}", name="taxonomy_show")
     * @Method("GET")
     */
    public function showAction(Request $request, $uuid)
    {
        $model = $this->container->get('raphia_model');
        $taxonomyTree = $model->getByUnique('taxonomy_tree', ['uuid' => $uuid]);

        // $this->denyAccessUnlessGranted('view', $uRL);!!!!!!!!!!!!!!!!
        //^ TODO: SECURITY AUTHORIZATION

        $deleteForm = $this->createDeleteForm($taxonomyTree);
        
        //dump($taxonomyTree);
        //dump($request->query->getInt('page', 1));
        //dump($taxonomyTree['synonym_uuid']);
        //dump($this
                //->container->get('raphia_model')
                //// ->getMoreManyToManyWherePager('url', 'uuid', 'url_uuid',
                ////     'owned_url', 'uuid', 'owned_url_uuid',
                ////     'link_owned_url_user', 'user_uuid', 'uuid', 'user',
                ////     ['uuid' => $user->getId()])
                //->getWhereManyToManyToManyPager('taxonomy_leaf', 'uuid', 'taxonomy_uuid',
                    //'link_owned_url_taxonomy', 'url_uuid', 'uuid', 'owned_url', 'url_uuid', 'uuid', 'url',
                    //['uuid' => $taxonomyTree['synonym_uuid']]));

        return $this->render('@MultiTaxonomyDbalUtilBundle/show.html.twig', [
            'term' => $taxonomyTree,
            'uRLs' => $this
                ->container->get('raphia_model')
                // ->getMoreManyToManyWherePager('url', 'uuid', 'url_uuid',
                //     'owned_url', 'uuid', 'owned_url_uuid',
                //     'link_owned_url_user', 'user_uuid', 'uuid', 'user',
                //     ['uuid' => $user->getId()])
                ->getWhereManyToManyToManyPager('taxonomy_leaf', 'uuid', 'taxonomy_uuid',
                    'link_owned_url_taxonomy', 'owned_url_uuid', 'uuid', 'owned_url', 'url_uuid', 'uuid', 'url',
                    ['uuid' => $taxonomyTree['synonym_uuid']])
                // getManyToManyTraversable('owned_url', 'uuid', 'url_uuid', 'link_owned_url_taxonomy', 'taxonomy_uuid', 'uuid', 'taxonomy_leaf', {uuid: uRL.uuid})
                ->setMaxPerPage(100)
                ->setCurrentPage($request->query->getInt('page', 1)),
            'delete_form' => $deleteForm->createView(),
        ]);
    }


    /**
     * Deletes a taxonomy entity.
     *
     * @Route("/{uuid}", name="taxonomy_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $uuid)
    {
        // TODO: authorization
        
        $model = $this->container->get('raphia_model');
        $taxonomyTree = $model->getByUnique('taxonomy_tree', ['uuid' => $uuid]);

        $form = $this->createDeleteForm($taxonomyTree);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $model->deleteByUnique('taxonomy_tree', ['uuid' => $uuid]);
        }

        return $this->redirectToRoute('taxonomy_index');
    }
    // SELECT CASE EXISTS (SELECT * FROM owned_url WHERE url_uuid = $uRL['url_uuid'])
    //     WHEN false THEN (DELETE url WHERE uuid = $uRL['url_uuid'])
    // END;
    // PREPARE url_garbage_collect(uuid) AS
    //     SELECT CASE EXISTS (SELECT * FROM owned_url WHERE url_uuid = $1)
    //         WHEN false THEN (DELETE FROM url WHERE uuid = $1) -- syntax error near from
    //     END;
    // Anyways one should implement deletion date field before really deleting...

    /**
     * Creates a form to delete a uRL entity.
     *
     * @param array $uRL The uRL array
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(array $taxonomyTree)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('taxonomy_delete', $taxonomyTree))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
