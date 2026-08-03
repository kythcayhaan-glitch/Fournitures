<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803135208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le flag de changement de mot de passe obligatoire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP must_change_password');
    }
}
