<?php declare(strict_types=1);

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\MagazineRepository;
use App\Factory\ContentManagerFactory;
use Symfony\UX\Chartjs\Model\Chart;
use App\Repository\UserRepository;
use App\Service\MagazineManager;
use App\Form\MagazineThemeType;
use App\Service\ReportManager;
use App\Service\BadgeManager;
use App\DTO\MagazineThemeDto;
use App\Form\MagazineBanType;
use App\DTO\MagazineBanDto;
use App\Form\ModeratorType;
use App\Form\MagazineType;
use App\DTO\ModeratorDto;
use App\Entity\Moderator;
use App\Entity\Magazine;
use App\Form\BadgeType;
use App\DTO\BadgeDto;
use App\Entity\Badge;
use App\Entity\Report;
use App\Entity\User;

class MagazinePanelController extends AbstractController
{
    public function __construct(
        private MagazineManager $manager,
    ) {
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="magazine")
     */
    public function front(Magazine $magazine, ChartBuilderInterface $chartBuilder): Response
    {
        $this->denyAccessUnlessGranted('edit_profile', $this->getUserOrThrow());

        return $this->getChartsResponse($magazine, $chartBuilder);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="magazine")
     */
    public function edit(Magazine $magazine, Request $request): Response
    {
        $magazineDto = $this->manager->createDto($magazine);

        $form = $this->createForm(MagazineType::class, $magazineDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->edit($magazine, $magazineDto);

            return $this->redirectToMagazine($magazine);
        }

        return $this->render(
            'magazine/panel/edit.html.twig',
            [
                'magazine' => $magazine,
                'form'     => $form->createView(),
            ]
        );
    }

    /**
     * @IsGranted("ROLE_USER")
     * @IsGranted("edit", subject="magazine")
     */
    public function theme(Magazine $magazine, Request $request): Response
    {
        $dto = new MagazineThemeDto($magazine);

        $form = $this->createForm(MagazineThemeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->changeTheme($dto);
        }

        return $this->render(
            'magazine/panel/theme.html.twig',
            [
                'magazine' => $magazine,
                'form'     => $form->createView(),
            ]
        );
    }

    private function getChartsResponse(Magazine $magazine, ChartBuilderInterface $chartBuilder): Response
    {
        $labels = ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Listopad', 'Grudzień'];

        $contentChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $contentChart->setData(
            [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'       => 'Treści',
                        'borderColor' => '#4382AD',
                        'data'        => [0, 10, 5, 2, 20, 30, 35, 23, 30, 35, 40],
                    ],
                    [
                        'label'       => 'Komentarze',
                        'borderColor' => '#6253ac',
                        'data'        => [0, 6, 11, 22, 10, 15, 25, 23, 35, 20, 11],
                    ],
                    [
                        'label'       => 'Wpisy',
                        'borderColor' => '#ac5353',
                        'data'        => [5, 15, 3, 15, 15, 24, 35, 36, 17, 11, 4],
                    ],
                ],
            ]
        );
        $contentChart->setOptions(
            [
                'scales' => [
                    'yAxes' => [
                        ['ticks' => ['min' => 0, 'max' => 40]],
                    ],
                ],
            ]
        );

        $voteChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $voteChart->setData(
            [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'       => 'Głosy pozytywne',
                        'borderColor' => '#4e805d',
                        'data'        => [0, 4, 5, 6, 7, 31, 38, 30, 22, 11, 11],
                    ],
                    [
                        'label'       => 'Głosy negatywne',
                        'borderColor' => '#b0403d',
                        'data'        => [0, 1, 3, 1, 4, 11, 4, 5, 3, 1, 11],
                    ],
                ],
            ]
        );
        $voteChart->setOptions(
            [
                'scales' => [
                    'yAxes' => [
                        ['ticks' => ['min' => 0, 'max' => 40]],
                    ],
                ],
            ]
        );

        return $this->render(
            'magazine/panel/front.html.twig',
            [
                'contentChart' => $contentChart,
                'voteChart'    => $voteChart,
                'magazine'     => $magazine,
            ]
        );
    }
}
