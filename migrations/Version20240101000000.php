<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale : création de toutes les tables de l'application.
 */
final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création initiale du schéma : user, category, fourniture, demande_materiel, ligne_demande, mouvement_stock';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            roles JSON NOT NULL COMMENT \'(DC2Type:json)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_active TINYINT(1) NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            INDEX idx_user_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE category (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_64C19C15E237E06 (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE fourniture (
            id INT AUTO_INCREMENT NOT NULL,
            category_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            reference VARCHAR(50) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            unit_price NUMERIC(10, 2) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            stock_quantity INT NOT NULL,
            stock_minimum INT NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_F7F1B0B1AEA34913 (reference),
            INDEX idx_fourniture_active (is_active),
            INDEX idx_fourniture_stock (stock_quantity),
            INDEX IDX_F7F1B0B112469DE2 (category_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE demande_materiel (
            id INT AUTO_INCREMENT NOT NULL,
            requester_id INT NOT NULL,
            processed_by_id INT DEFAULT NULL,
            reference VARCHAR(30) NOT NULL,
            motif LONGTEXT NOT NULL,
            statut VARCHAR(20) NOT NULL,
            requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            commentaire LONGTEXT DEFAULT NULL,
            UNIQUE INDEX UNIQ_A6A4EDBEAEA34913 (reference),
            INDEX idx_demande_statut (statut),
            INDEX idx_demande_date (requested_at),
            INDEX idx_demande_requester (requester_id),
            INDEX IDX_A6A4EDBE3256C025 (processed_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE ligne_demande (
            id INT AUTO_INCREMENT NOT NULL,
            fourniture_id INT NOT NULL,
            demande_id INT NOT NULL,
            quantite_demandee INT NOT NULL,
            quantite_servie INT NOT NULL,
            INDEX IDX_3E254FA1F5024FE7 (fourniture_id),
            INDEX IDX_3E254FA180E95E18 (demande_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE mouvement_stock (
            id INT AUTO_INCREMENT NOT NULL,
            fourniture_id INT NOT NULL,
            operateur_id INT NOT NULL,
            demande_id INT DEFAULT NULL,
            type VARCHAR(20) NOT NULL,
            quantite INT NOT NULL,
            quantite_avant INT NOT NULL,
            quantite_apres INT NOT NULL,
            motif VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_mouvement_date (created_at),
            INDEX idx_mouvement_type (type),
            INDEX IDX_29A03C78F5024FE7 (fourniture_id),
            INDEX IDX_29A03C78D02E2B03 (operateur_id),
            INDEX IDX_29A03C7880E95E18 (demande_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE fourniture ADD CONSTRAINT FK_F7F1B0B112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE demande_materiel ADD CONSTRAINT FK_A6A4EDBE2BB6451F FOREIGN KEY (requester_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE demande_materiel ADD CONSTRAINT FK_A6A4EDBE3256C025 FOREIGN KEY (processed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ligne_demande ADD CONSTRAINT FK_3E254FA1F5024FE7 FOREIGN KEY (fourniture_id) REFERENCES fourniture (id)');
        $this->addSql('ALTER TABLE ligne_demande ADD CONSTRAINT FK_3E254FA180E95E18 FOREIGN KEY (demande_id) REFERENCES demande_materiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_29A03C78F5024FE7 FOREIGN KEY (fourniture_id) REFERENCES fourniture (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_29A03C78D02E2B03 FOREIGN KEY (operateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_29A03C7880E95E18 FOREIGN KEY (demande_id) REFERENCES demande_materiel (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_29A03C7880E95E18');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_29A03C78D02E2B03');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_29A03C78F5024FE7');
        $this->addSql('ALTER TABLE ligne_demande DROP FOREIGN KEY FK_3E254FA180E95E18');
        $this->addSql('ALTER TABLE ligne_demande DROP FOREIGN KEY FK_3E254FA1F5024FE7');
        $this->addSql('ALTER TABLE demande_materiel DROP FOREIGN KEY FK_A6A4EDBE3256C025');
        $this->addSql('ALTER TABLE demande_materiel DROP FOREIGN KEY FK_A6A4EDBE2BB6451F');
        $this->addSql('ALTER TABLE fourniture DROP FOREIGN KEY FK_F7F1B0B112469DE2');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE ligne_demande');
        $this->addSql('DROP TABLE demande_materiel');
        $this->addSql('DROP TABLE fourniture');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE `user`');
    }
}
