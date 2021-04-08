<?php declare(strict_types=1);

namespace App\ApiDataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\DTO\EntryDto;
use App\DTO\MagazineDto;
use App\Factory\EntryFactory;
use App\Factory\MagazineFactory;
use App\PageView\EntryPageView;
use App\Repository\EntryRepository;
use App\Repository\MagazineRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final class EntryCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private EntryRepository $entryRepository,
        private EntryFactory $entryFactory,
        private RequestStack $request
    ) {
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return EntryDto::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        try {
            $criteria = new EntryPageView(1);
            $entries  = $this->entryRepository->findByCriteria($criteria);
        } catch (\Exception $e) {
            return [];
        }

        foreach ($entries as $entry) {
            yield $this->entryFactory->createDto($entry);
        }
    }
}
