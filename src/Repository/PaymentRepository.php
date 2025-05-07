<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function findByMollieId(string $molliePaymentId): ?Payment
    {
        return $this->findOneBy(['molliePaymentId' => $molliePaymentId]);
    }
    
    public function findByPaymentContextToken(string $token): ?Payment
    {
        return $this->findOneBy(['paymentContextToken' => $token]);
    }
    
    public function findPendingPaymentsByUser(User $user): array
    {
        return $this->findBy([
            'user' => $user,
            'status' => Payment::STATUS_PENDING
        ]);
    }
}