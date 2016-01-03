<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Wiki;

class PageController extends Controller
{
    /**
     * @Route("/wiki/{slug}/edit/{page}", name="page_edit", requirements={
     *     "page": "[\d\w-_\/\.+@*]+"
     * }, defaults={
     *     "page": "index"
     * }))
     * @Method("GET")
     */
    public function editAction($slug, $page)
    {
        $wikiRepository = $this->getDoctrine()->getRepository('AppBundle:Wiki');
        $wiki = $wikiRepository->findOneBySlug($slug);
        
        $repository = $this->get('app.repository')->getRepository($slug);
        $branch = $repository->getReferences()->getBranch('master');
        $commit = $branch->getCommit();
        $tree = $commit->getTree();
        $blob = $tree->resolvePath($page . '.md');
        
        return $this->render('page/edit.html.twig', array(
            'wiki' => $wiki,
            'tree' => $tree->getEntries(),
            'content' => $blob->getContent(),
            'path' => $page
        ));
    }
    
    /**
     * @Route("/wiki/{slug}/edit/{page}", name="page_update", requirements={
     *     "page": "[\d\w-_\/\.+@*]+"
     * }, defaults={
     *     "page": "index"
     * }))
     * @Method("POST")
     */
    public function updateAction($slug, $page, Request $request)
    {
        $wikiRepository = $this->getDoctrine()->getRepository('AppBundle:Wiki');
        $wiki = $wikiRepository->findOneBySlug($slug);
        
        $repository = $this->get('app.repository')->getRepository($slug);
        
        $path = $repository->getWorkingDir();
        
        $content = $request->request->get('content');
        
        $message = $request->request->get('message');
        
        if (strlen($message) === 0) {
            $message = 'Update page ' . $page . '.md';
        }
        
        $fs = new Filesystem();
        $fs->dumpFile($path . '/' . $page . '.md', $content);
        
        $repository->run('add', array('-A'));
        $repository->run('commit', array('-m ' . $message, '--author="Gitdown wiki <wiki@example.com>"'));
        
        return $this->redirectToRoute('page_show', array('slug' => $slug, 'page' => $page));
    }
    
    /**
     * @Route("/wiki/{slug}/{page}", name="page_show", requirements={
     *     "page": "[\d\w-_\/\.+@*]+"
     * }, defaults={
     *     "page": "index"
     * }))
     * @Method("GET")
     */
    public function showAction($slug, $page)
    {
        $wikiRepository = $this->getDoctrine()->getRepository('AppBundle:Wiki');
        $wiki = $wikiRepository->findOneBySlug($slug);
        
        $repository = $this->get('app.repository')->getRepository($slug);
        $branch = $repository->getReferences()->getBranch('master');
        $commit = $branch->getCommit();
        $tree = $commit->getTree();
        $blob = $tree->resolvePath($page . '.md');
        
        return $this->render('page/show.html.twig', array(
            'wiki' => $wiki,
            'tree' => $tree->getEntries(),
            'content' => $blob->getContent(),
            'path' => $page
        ));
    }
}