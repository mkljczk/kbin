<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Contracts\CommentInterface;
use App\Entity\Contracts\DomainInterface;
use App\Entity\Contracts\VoteInterface;
use App\Entity\Traits\CreatedAtTrait;
use App\Entity\Traits\VotableTrait;
use App\Repository\EntryRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/**
 * @ORM\Entity(repositoryClass=EntryRepository::class)
 */
class Entry implements VoteInterface, CommentInterface, DomainInterface
{
    use VotableTrait;
    use CreatedAtTrait {
        CreatedAtTrait::__construct as createdAtTraitConstruct;
    }

    private const DOWNVOTED_CUTOFF = -5;
    private const NETSCORE_MULTIPLIER = 1800;
    private const COMMENT_MULTIPLIER = 5000;
    private const COMMENT_DOWNVOTED_MULTIPLIER = 500;
    private const MAX_ADVANTAGE = 86400;
    private const MAX_PENALTY = 43200;
    const ENTRY_TYPE_ARTICLE = 'artykul';
    const ENTRY_TYPE_LINK = 'link';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="entries")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity=Magazine::class, inversedBy="entries")
     * @ORM\JoinColumn(nullable=false, onDelete="cascade")
     */
    private ?Magazine $magazine;

    /**
     * @ORM\ManyToOne(targetEntity="Image", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Image $image = null;

    /**
     * @ORM\ManyToOne(targetEntity=Domain::class, inversedBy="entries")
     */
    private Domain $domain;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $url = null;

    /**
     * @ORM\Column(type="text", nullable=true, length=15000)
     */
    private ?string $body = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $embed = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $commentCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $score = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $ranking = 0;

    /**
     * @ORM\Column(type="datetimetz")
     */
    private ?\DateTime $lastActive;

    /**
     * @ORM\OneToMany(targetEntity=EntryComment::class, mappedBy="entry", orphanRemoval=true)
     */
    private Collection $comments;

    /**
     * @ORM\OneToMany(targetEntity=EntryVote::class, mappedBy="entry", cascade={"persist"},
     *     fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private Collection $votes;

    public function __construct(string $title, ?string $url, ?string $body, Magazine $magazine, User $user)
    {
        $this->title    = $title;
        $this->url      = $url;
        $this->body     = $body;
        $this->magazine = $magazine;
        $this->user     = $user;
        $this->comments = new ArrayCollection();
        $this->votes    = new ArrayCollection();

        $user->addEntry($this);

        $this->createdAtTraitConstruct();

        $this->updateLastActive();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMagazine(): Magazine
    {
        return $this->magazine;
    }

    public function setMagazine(?Magazine $magazine): self
    {
        $this->magazine = $magazine;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getEmbed(): ?string
    {
        return $this->embed;
    }

    public function setEmbed(?string $embed): self
    {
        $this->embed = $embed;

        return $this;
    }

    public function getCommentCount(): ?int
    {
        return $this->commentCount;
    }

    public function setCommentCount(int $commentCount): self
    {
        $this->commentCount = $commentCount;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getRanking(): int
    {
        return $this->ranking;
    }

    public function setRanking(int $ranking): void
    {
        $this->ranking = $ranking;
    }


    public function getLastActive(): ?\DateTime
    {
        return $this->lastActive;
    }

    public function updateLastActive(): void
    {
        $this->comments->get(-1);

        $criteria = Criteria::create()
            ->orderBy(['createdAt' => 'DESC'])
            ->setMaxResults(1);

        $lastComment = $this->comments->matching($criteria)->first();

        if ($lastComment) {
            $this->lastActive = \DateTime::createFromImmutable($lastComment->getCreatedAt());
        } else {
            $this->lastActive = \DateTime::createFromImmutable($this->getCreatedAt());
        }
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(EntryComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setEntry($this);
        }

        $this->updateCounts();
        $this->updateRanking();
        $this->updateLastActive();

        return $this;
    }

    public function removeComment(EntryComment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getEntry() === $this) {
                $comment->setEntry(null);
            }
        }

        $this->updateCounts();
        $this->updateRanking();
        $this->updateLastActive();

        return $this;
    }

    private function updateCounts(): self
    {
        $this->setCommentCount(
            $this->getComments()->count()
        );

        return $this;
    }

    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): self
    {
        Assert::isInstanceOf($vote, EntryVote::class);

        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setEntry($this);
        }

        $this->score = $this->getUpVotes()->count() - $this->getDownVotes()->count();
        $this->updateRanking();

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        Assert::isInstanceOf($vote, EntryVote::class);

        if ($this->votes->removeElement($vote)) {
            if ($vote->getEntry() === $this) {
                $vote->setEntry(null);
            }
        }

        $this->score = $this->getUpVotes()->count() - $this->getDownVotes()->count();
        $this->updateRanking();

        return $this;
    }

    private function updateRanking(): self
    {
        $score          = $this->getScore();
        $scoreAdvantage = $score * self::NETSCORE_MULTIPLIER;

        if ($score > self::DOWNVOTED_CUTOFF) {
            $commentAdvantage = $this->getCommentCount() * self::COMMENT_MULTIPLIER;
        } else {
            $commentAdvantage = $this->getCommentCount() * self::COMMENT_DOWNVOTED_MULTIPLIER;
        }

        $advantage = max(min($scoreAdvantage + $commentAdvantage, self::MAX_ADVANTAGE), -self::MAX_PENALTY);

        $this->ranking = $this->getCreatedAt()->getTimestamp() + $advantage;

        return $this;
    }

    public function __sleep()
    {
        return [];
    }

}
