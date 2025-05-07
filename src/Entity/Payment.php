<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $molliePaymentId = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column]
    private ?int $credits = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = 'EUR';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $checkoutUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentContextToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->paymentContextToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMolliePaymentId(): ?string
    {
        return $this->molliePaymentId;
    }

    public function setMolliePaymentId(string $molliePaymentId): static
    {
        $this->molliePaymentId = $molliePaymentId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        if ($status === self::STATUS_PAID && $this->paidAt === null) {
            $this->paidAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function setCheckoutUrl(?string $checkoutUrl): static
    {
        $this->checkoutUrl = $checkoutUrl;

        return $this;
    }

    public function getPaymentContextToken(): ?string
    {
        return $this->paymentContextToken;
    }

    public function setPaymentContextToken(?string $paymentContextToken): static
    {
        $this->paymentContextToken = $paymentContextToken;

        return $this;
    }
}