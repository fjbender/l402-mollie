<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250507072410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, mollie_payment_id VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, amount DOUBLE PRECISION NOT NULL, credits INTEGER NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , paid_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            , checkout_url VARCHAR(255) DEFAULT NULL, payment_context_token VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6D28840DA76ED395 ON payment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
            , password VARCHAR(255) NOT NULL, credits INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , last_credit_update_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , token VARCHAR(36) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D6495F37A13B ON "user" (token)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE payment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
