<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme fourniture → article (table, colonnes FK) et supprime is_active.
 */
final class Version20260717135708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme fourniture en article (table, FK) et supprime la notion active/inactive';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE fourniture TO article');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY `FK_1384640712469DE2`');
        $this->addSql('ALTER TABLE article DROP is_active');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_fourniture_stock TO idx_article_stock');
        $this->addSql('ALTER TABLE article RENAME INDEX UNIQ_13846407AEA34913 TO UNIQ_23A0E66AEA34913');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');

        $this->addSql('ALTER TABLE ligne_demande DROP FOREIGN KEY `FK_B90DE99C884A3A3C`');
        $this->addSql('ALTER TABLE ligne_demande CHANGE fourniture_id article_id INT NOT NULL');
        $this->addSql('ALTER TABLE ligne_demande RENAME INDEX idx_b90de99c884a3a3c TO IDX_B90DE99C7294869C');
        $this->addSql('ALTER TABLE ligne_demande ADD CONSTRAINT FK_B90DE99C7294869C FOREIGN KEY (article_id) REFERENCES article (id)');

        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY `FK_61E2C8EB884A3A3C`');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE fourniture_id article_id INT NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock RENAME INDEX idx_61e2c8eb884a3a3c TO IDX_61E2C8EB7294869C');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EB7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EB7294869C');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE article_id fourniture_id INT NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock RENAME INDEX idx_61e2c8eb7294869c TO IDX_61E2C8EB884A3A3C');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT `FK_61E2C8EB884A3A3C` FOREIGN KEY (fourniture_id) REFERENCES article (id)');

        $this->addSql('ALTER TABLE ligne_demande DROP FOREIGN KEY FK_B90DE99C7294869C');
        $this->addSql('ALTER TABLE ligne_demande CHANGE article_id fourniture_id INT NOT NULL');
        $this->addSql('ALTER TABLE ligne_demande RENAME INDEX idx_b90de99c7294869c TO IDX_B90DE99C884A3A3C');
        $this->addSql('ALTER TABLE ligne_demande ADD CONSTRAINT `FK_B90DE99C884A3A3C` FOREIGN KEY (fourniture_id) REFERENCES article (id)');

        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6612469DE2');
        $this->addSql('ALTER TABLE article ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_article_stock TO idx_fourniture_stock');
        $this->addSql('ALTER TABLE article RENAME INDEX uniq_23a0e66aea34913 TO UNIQ_13846407AEA34913');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT `FK_1384640712469DE2` FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('RENAME TABLE article TO fourniture');
    }
}
