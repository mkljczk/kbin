<?php

declare(strict_types=1);

namespace App\Controller\Tag;

use App\Controller\AbstractController;
use App\Repository\TagRepository;
use App\Service\TagManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TagOverviewController extends AbstractController
{
    public function __construct(private readonly TagManager $tagManager, private readonly TagRepository $tagRepository)
    {
    }

    public function __invoke(string $name, Request $request): Response
    {
        return $this->render(
            'tag/overview.html.twig',
            [
                'tag' => $name,
                'results' => $this->tagRepository->findOverall(
                    $this->getPageNb($request),
                    $this->tagManager->transliterate(strtolower($name))
                ),
            ]
//            ['q' => '#'.$name, 'results' => $this->manager->findByTagPaginated($name, $this->getPageNb($request))]
        );
    }
}