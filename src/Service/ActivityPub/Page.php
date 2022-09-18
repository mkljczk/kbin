<?php declare(strict_types=1);

namespace App\Service\ActivityPub;

use App\DTO\EntryDto;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Entity\Contracts\VisibilityInterface;
use App\Entity\User;
use App\Repository\ApActivityRepository;
use App\Repository\MagazineRepository;
use App\Service\ActivityPubManager;
use App\Service\EntryManager;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class Page
{
    public function __construct(
        private ApActivityRepository $repository,
        private MarkdownConverter $markdownConverter,
        private MagazineRepository $magazineRepository,
        private EntryManager $entryManager,
        private ActivityPubManager $activityPubManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function create(array $object): ActivityPubActivityInterface
    {
        $current = $this->repository->findByObjectId($object['id']);
        if ($current) {
            return $this->entityManager->getRepository($current['type'])->find((int)$current['id']);
        }

        $dto = new EntryDto();
        $dto->magazine = $this->magazineRepository->findOneByName('random'); // @todo magazine by tags
        $dto->title = $object['name'];
        $dto->apId = $object['id'];

        if (isset($object['attachment']) || isset($object['image'])) {
            $dto->image = $this->activityPubManager->handleImages($object['attachment']);
        }

        $actor = $this->activityPubManager->findActorOrCreate($object['attributedTo']);

        $dto->body = $object['content'] ? $this->markdownConverter->convert($object['content']) : null;
        $dto->visibility = $this->getVisibility($object, $actor);
        $this->handleUrl($dto, $object);
        $this->handleDate($dto, $object['published']);

        return $this->entryManager->create(
            $dto,
            $actor,
            false
        );
    }

    private function handleDate(EntryDto $dto, string $date): void
    {
        $dto->createdAt = new DateTimeImmutable($date);
        $dto->lastActive = new DateTime($date);
    }

    private function handleUrl(EntryDto $dto, ?array $object): void
    {
        $attachment = $object['attachment'];

        try {
            if (is_array($attachment)) {
                $link = array_filter(
                    $attachment,
                    fn($val) => in_array($val['type'], ['Link'])
                );

                $dto->url = $link[0]['href'];
            }
        } catch (\Exception $e) {
        }

        if (!$dto->url && isset($object['url'])) {
            $dto->url = $object['url'];
        }
    }

    private function getVisibility(array $object, User $actor): string
    {
        if (!in_array(
            ActivityPubActivityInterface::PUBLIC_URL,
            array_merge($object['to'] ?? [], $object['cc'] ?? [])
        )) {
            if (
                !in_array(
                    $actor->apFollowersUrl,
                    array_merge($object['to'] ?? [], $object['cc'] ?? [])
                )
            ) {
                throw new \Exception('PM: not implemented.');
            }

            return VisibilityInterface::VISIBILITY_PRIVATE;
        }

        return VisibilityInterface::VISIBILITY_VISIBLE;
    }
}
