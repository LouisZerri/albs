<?php

namespace App\Entity;

use App\Repository\LineDiscussionReplyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LineDiscussionReplyRepository::class)]
class LineDiscussionReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LineDiscussion $discussion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $isEdited = null;

    /**
     * @var Collection<int, ForumImage>
     */
    #[ORM\OneToMany(targetEntity: ForumImage::class, mappedBy: 'reply', fetch: 'EAGER')]
    private Collection $images;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isEdited = false;
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscussion(): ?LineDiscussion
    {
        return $this->discussion;
    }

    public function setDiscussion(?LineDiscussion $discussion): static
    {
        $this->discussion = $discussion;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isEdited(): ?bool
    {
        return $this->isEdited;
    }

    public function setIsEdited(bool $isEdited): static
    {
        $this->isEdited = $isEdited;

        return $this;
    }

    /**
     * @return Collection<int, ForumImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ForumImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setReply($this);
        }

        return $this;
    }

    public function removeImage(ForumImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getReply() === $this) {
                $image->setReply(null);
            }
        }

        return $this;
    }
}
