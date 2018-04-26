<?php

namespace App\Controller\Framework;

use App\Command\CommandDispatcherTrait;
use App\Command\Framework\AddItemCommand;
use App\Command\Framework\ChangeItemParentCommand;
use App\Command\Framework\CopyItemToDocCommand;
use App\Command\Framework\DeleteItemCommand;
use App\Command\Framework\LockItemCommand;
use App\Command\Framework\RemoveChildCommand;
use App\Command\Framework\UpdateItemCommand;
use App\Exception\AlreadyLockedException;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsDefAssociationGrouping;
use App\Form\Command\ChangeLsItemParentCommand;
use App\Form\Command\CopyToLsDocCommand;
use App\Form\Type\LsDocListType;
use App\Form\Type\LsItemParentType;
use App\Form\Type\LsItemType;
use App\Entity\User\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
/**
 * LsItem controller.
 *
 * @Route("/cfitem")
 */
class LsItemController extends AbstractController
{
    use CommandDispatcherTrait;

    /**
     * Lists all LsItem entities.
     *
     * @Route("/", name="lsitem_index")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lsItems = $em->getRepository(LsItem::class)->findAll();

        return [
            'lsItems' => $lsItems,
        ];
    }

    /**
     * Creates a new LsItem entity.
     *
     * @Route("/new/{doc}/{parent}", name="lsitem_new")
     * @Route("/new/{doc}/{parent}/{assocGroup}", name="lsitem_new_ag")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('add-standard-to', doc)")
     *
     * @param Request $request
     * @param LsDoc $doc
     * @param LsItem|null $parent
     * @param LsDefAssociationGrouping|null $assocGroup
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction(Request $request, LsDoc $doc, LsItem $parent = null, LsDefAssociationGrouping $assocGroup = null)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsItem = new LsItem();

        $lsItem->setLsDoc($doc);
        $lsItem->setLsDocUri($doc->getUri());

        $form = $this->createForm(LsItemType::class, $lsItem, ['ajax' => $ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $command = new AddItemCommand($lsItem, $doc, $parent, $assocGroup);
                $this->sendCommand($command);

                // retrieve isChildOf assoc id for the new item
                $assoc = $this->getDoctrine()->getRepository(LsAssociation::class)->findOneBy(['originLsItem' => $lsItem]);

                if ($ajax) {
                    // if ajax call, return the item as json
                    return $this->generateItemJsonResponse($lsItem, $assoc);
                }

                return $this->redirectToRoute('lsitem_show', array('id' => $lsItem->getId()));
            } catch (\Exception $e) {
                $form->addError(new FormError('Error adding new item: '.$e->getMessage()));
            }
        }

        $ret = [
            'lsItem' => $lsItem,
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted()) {
            return $this->render('framework/ls_item/new.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Finds and displays a LsItem entity.
     *
     * @Route("/{id}.{_format}", defaults={"_format"="html"}, name="lsitem_show")
     * @Method("GET")
     * @Template()
     *
     * @param LsItem $lsItem
     * @param string $_format
     *
     * @return array
     */
    public function showAction(LsItem $lsItem, $_format = 'html')
    {
        if ('json' === $_format) {
            // Redirect?  Change Action for Template?
            return ['lsItem' => $lsItem];
        }

        $deleteForm = $this->createDeleteForm($lsItem);

        return [
            'lsItem' => $lsItem,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing LsItem entity.
     *
     * @Route("/{id}/edit", name="lsitem_edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     * @param User $user
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, LsItem $lsItem, UserInterface $user)
    {
        $ajax = $request->isXmlHttpRequest();

        try {
            $command = new LockItemCommand($lsItem, $user);
            $this->sendCommand($command);
        } catch (AlreadyLockedException $e) {
            return $this->render(
                'framework/ls_item/locked.html.twig',
                []
            );
        }

        $deleteForm = $this->createDeleteForm($lsItem);
        $editForm = $this->createForm(LsItemType::class, $lsItem, ['ajax' => $ajax]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //echo '<pre>';
           // print_r($_POST);die;
            try {
                $command = new UpdateItemCommand($lsItem);
                $this->sendCommand($command);

                if ($ajax) {
                    // if ajax call, return the item as json
                    return $this->generateItemJsonResponse($lsItem);
                }

                return $this->redirectToRoute('lsitem_edit', ['id' => $lsItem->getId()]);
            } catch (\Exception $e) {
                $editForm->addError(new FormError('Error updating item: '.$e->getMessage()));
            }
        }

        $ret = [
            'lsItem' => $lsItem,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];

        if ($ajax && $editForm->isSubmitted() && !$editForm->isValid()) {
            return $this->render('framework/ls_item/edit.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Deletes a LsItem entity.
     *
     * @Route("/{id}", name="lsitem_delete")
     * @Method("DELETE")
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LsItem $lsItem)
    {
        $form = $this->createDeleteForm($lsItem);
        $form->handleRequest($request);

        $hasChildren = $lsItem->getChildren();

        if ($form->isSubmitted() && $form->isValid() && $hasChildren->isEmpty()) {
            $command = new DeleteItemCommand($lsItem);
            $this->sendCommand($command);
        }

        return $this->redirectToRoute('lsitem_index');
    }

    /**
     * Creates a form to delete a LsItem entity.
     *
     * @param LsItem $lsItem The LsItem entity
     *
     * @return FormInterface The form
     */
    private function createDeleteForm(LsItem $lsItem): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('lsitem_delete', array('id' => $lsItem->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Export an LsItem entity.
     *
     * @Route("/{id}/export", defaults={"_format"="json"}, name="lsitem_export")
     * @Method("GET")
     * @Template()
     *
     * @param LsItem $lsItem
     *
     * @return array
     */
    public function exportAction(LsItem $lsItem)
    {
        return [
            'lsItem' => $lsItem,
        ];
    }

    /**
     * Remove a child LSItem
     *
     * @Route("/{id}/removeChild/{child}", name="lsitem_remove_child")
     * @Method("POST")
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     *
     * @param \App\Entity\Framework\LsItem $parent
     * @param \App\Entity\Framework\LsItem $child
     *
     * @return array
     */
    public function removeChildAction(LsItem $parent, LsItem $child)
    {
        $command = new RemoveChildCommand($parent, $child);
        $this->sendCommand($command);

        return [];
    }

    /**
     * Copy an LsItem to a new LsDoc
     *
     * @Route("/{id}/copy", name="lsitem_copy_item")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     *
     * @param Request $request
     * @param LsItem $lsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function copyAction(Request $request, LsItem $lsItem)
    {
        // Steps
        //  - Select LsDoc to copy to
        //  - Clone LsItem to selected LsDoc

        $ajax = $request->isXmlHttpRequest();

        $command = new CopyToLsDocCommand();
        $form = $this->createForm(LsDocListType::class, $command->convertToDTO($lsItem), ['ajax' => $ajax]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $copyCommand = new CopyItemToDocCommand($form->getData());
            $this->sendCommand($copyCommand);
            $newItem = $copyCommand->getNewItem();

            if ($ajax) {
                return new Response(
                    $this->generateUrl('doc_tree_item_view', ['id' => $newItem->getId()]),
                    Response::HTTP_CREATED,
                    [
                        'Location' => $this->generateUrl('doc_tree_item_view', ['id' => $newItem->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }

            return $this->redirectToRoute('lsitem_show', array('id' => $lsItem->getId()));
        }

        $ret = [
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('framework/ls_item/copy.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    /**
     * Displays a form to change the parent of an existing LsItem entity.
     *
     * @Route("/{id}/parent", name="lsitem_change_parent")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', lsItem)")
     * @Template()
     *
     * @param Request $request
     * @param LsItem $lsItem
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function changeParentAction(Request $request, LsItem $lsItem)
    {
        $ajax = $request->isXmlHttpRequest();

        $lsDoc = $lsItem->getLsDoc();

        $command = new ChangeLsItemParentCommand();
        $form = $this->createForm(LsItemParentType::class, $command->convertToDTO($lsItem), ['ajax' => $ajax, 'lsDoc' => $lsDoc]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $changeCommand = new ChangeItemParentCommand($form->getData());
            $this->sendCommand($changeCommand);

            if ($ajax) {
                return new Response($this->generateUrl('doc_tree_item_view', ['id' => $lsItem->getId()]), Response::HTTP_ACCEPTED);
            }

            return $this->redirectToRoute('lsitem_edit', ['id' => $lsItem->getId()]);
        }

        $ret = [
            'lsItem' => $lsItem,
            'lsDoc' => $lsDoc,
            'form' => $form->createView(),
        ];

        if ($ajax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render('framework/ls_item/change_parent.html.twig', $ret, new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $ret;
    }

    private function generateItemJsonResponse(LsItem $item, ?LsAssociation $assoc = null): Response
    {
        $ret = [
            'id' => $item->getId(),
            'identifier' => $item->getIdentifier(),
            'fullStatement' => $item->getFullStatement(),
            'humanCodingScheme' => $item->getHumanCodingScheme(),
            'listEnumInSource' => $item->getListEnumInSource(),
            'abbreviatedStatement' => $item->getAbbreviatedStatement(),
            'conceptKeywords' => $item->getConceptKeywords(),
            'conceptKeywordsUri' => $item->getConceptKeywordsUri(),
            'notes' => $item->getNotes(),
            'language' => $item->getLanguage(),
            'educationalAlignment' => $item->getEducationalAlignment(),
            'itemType' => $item->getItemType(),
            'changedAt' => $item->getChangedAt(),
            'extra' => [],
        ];

        if (null !== $assoc) {
            $destItem = $assoc->getDestinationNodeIdentifier();

            if (null !== $destItem) {
                $ret['extra'] = [
                    'assocDoc' => $assoc->getLsDocIdentifier(),
                    'assocId' => $assoc->getId(),
                    'identifier' => $assoc->getIdentifier(),
                    //'groupId' => (null !== $assoc->getGroup()) ? $assoc->getGroup()->getId() : null,
                    'dest' => ['doc' => $assoc->getLsDocIdentifier(), 'item' => $destItem, 'uri' => $destItem],
                ];
                if ($assoc->getGroup()) {
                    $ret['extra']['groupId'] = $assoc->getGroup()->getId();
                }
                if ($assoc->getSequenceNumber()) {
                    $ret['extra']['seq'] = $assoc->getSequenceNumber();
                }
            }
        }

        $response = new Response($this->renderView('framework/doc_tree/export_item.json.twig', ['lsItem' => $ret]));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
    /**
     * Upload attachment to LsItem entity.
     *
     * @Route("/{id}/upload-attachment", name="lsitem_upload_attachment")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     * @param User $user
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */

    public function uploadAttachmentAction(Request $request, LsItem $lsItem, UserInterface $user)
    {
            
            $output = array('uploaded' => false);
            // get the file from the request object
            $file = $request->files->get('file');
            $attachmentTo=$request->get('attachmentTo');
            // generate a new filename (safer, better approach)
            // To use original filename, $fileName = $this->file->getClientOriginalName();
            //$fileName = md5(uniqid()).'.'.$file->guessExtension();
            $fileName = $file->getClientOriginalName();
            // set your uploads directory
            $uploadDir =dirname(__DIR__).'../../../web/uploads/'. $attachmentTo.'/';
            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            if ($file->move($uploadDir, $fileName)) { 
               $output['uploaded'] = true;
               $output['fileName'] = $fileName;
            }
            return new JsonResponse($output);
    }
    
    /**
     * delete attachment.
     *
     * @Route("/{id}/delete-attachment", name="lsitem_delete_attachment")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('edit', lsItem)")
     *
     * @param Request $request
     * @param LsItem $lsItem
     * @param User $user
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */

    public function deleteAttachmentAction(Request $request, LsItem $lsItem, UserInterface $user)
    {
        $output = array('uploaded' => false);
        // get the file from the request object
        $file = $request->files->get('file');
        $attachmentTo=$request->get('attachmentTo');
        $uploadDir =dirname(__DIR__).'../../../web/uploads/'. $attachmentTo.'/';
        $fileName=$uploadDir.'/'.$request->get('name');
        $fs = new Filesystem();
        if($fs->remove($fileName)){
            $output['delete'] = true;
        }
        return new JsonResponse($output);
    }
       
    
    
}
