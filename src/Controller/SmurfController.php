<?php

namespace App\Controller;

use App\Entity\Smurf;
use App\Form\SmurfType;
use App\Repository\SmurfRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/smurf')]
class SmurfController extends AbstractController
{
    #[Route('/', name: 'smurf_index', methods: ['GET'])]
    public function index(SmurfRepository $smurfRepository): Response
    {
        return $this->render('smurf/index.html.twig', [
            'smurves' => []
        ]);
    }

    #[Route('/new', name: 'smurf_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $smurf = new Smurf();
        $form = $this->createForm(SmurfType::class, $smurf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('smurf_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('smurf/new.html.twig', [
            'smurf' => $smurf,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'smurf_show', methods: ['GET'])]
    public function show(Smurf $smurf): Response
    {
        return $this->render('smurf/show.html.twig', [
            'smurf' => $smurf,
        ]);
    }
}
