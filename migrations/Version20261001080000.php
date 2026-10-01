<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend le motif des demandes facultatif (aligné sur l\'entité)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_materiel MODIFY motif LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE demande_materiel SET motif = '' WHERE motif IS NULL");
        $this->addSql('ALTER TABLE demande_materiel MODIFY motif LONGTEXT NOT NULL');
    }
}
